<?php

namespace Tests\Feature\Customization;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * M.1-a: QR codes are user-sensitive content and must live on the private
 * "local" disk, never the public disk; every preview is served through a
 * gate — a signed URL for the public customizer preview, and an
 * admin + OrderItem-ownership-bound route for the admin panel — with a
 * realpath guard that confines every read to the private disk root so
 * traversal/foreign paths and unauthenticated/unauthorized callers all
 * resolve to 403/404 instead of leaking file bytes.
 */
class QrCodeServingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Product $product     ;
    private Color $color          ;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'color_code' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'کارت فلزی کلاسیک',
            'slug' => 'classic-metal-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح شیر',
            'slug' => 'lion-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/lion-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create();
    }

    protected function tearDown(): void
    {
        // Clear any private file stashed during the test so it never leaks
        // between cases even when we couldn't fake the disk.
        foreach (Storage::disk('local')->allFiles('customizations/qr_codes') as $file) {
            Storage::disk('local')->delete($file);
        }

        parent::tearDown();
    }

    public function test_qr_file_is_stored_on_private_local_disk_not_public(): void
    {
        Storage::fake('local');

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('qr_code_enabled', true)
            ->set('qr_code_file', UploadedFile::fake()->image('qr.png', 200, 200));

        $path = $component->get('qr_code_path');

        $this->assertNotNull($path);
        $this->assertStringStartsWith('customizations/qr_codes/', $path);

        Storage::disk('local')->assertExists($path);
        // Must never land on the public disk where it could be hotlinked.
        Storage::disk('public')->assertMissing($path);
    }

    public function test_qr_preview_requires_a_valid_signature(): void
    {
        $this->writePrivateFile('customizations/qr_codes/secret.png', 'qr-preview-bytes');

        // Unsigned request to the (signed) preview route must be rejected.
        $this->get(route('customizations.qr.preview', ['path' => 'customizations/qr_codes/secret.png']))
            ->assertForbidden();

        $signedUrl = \URL::temporarySignedRoute(
            'customizations.qr.preview',
            now()->addMinutes(10),
            ['path' => 'customizations/qr_codes/secret.png']
        );

        $response = $this->get($signedUrl);
        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');

        ob_start();
        $response->baseResponse->sendContent();
        $this->assertStringContainsString('qr-preview-bytes', (string) ob_get_clean());
    }

    public function test_qr_preview_rejects_expired_signatures(): void
    {
        $this->writePrivateFile('customizations/qr_codes/expired.png', 'boom');

        $expiredUrl = \URL::temporarySignedRoute(
            'customizations.qr.preview',
            now()->subMinute(),
            ['path' => 'customizations/qr_codes/expired.png']
        );

        $this->get($expiredUrl)->assertForbidden();
    }

    public function test_qr_preview_rejects_path_traversal_with_the_realpath_guard(): void
    {
        // Even a *validly signed* URL cannot escape the private disk root.
        $signedUrl = \URL::temporarySignedRoute(
            'customizations.qr.preview',
            now()->addMinutes(10),
            ['path' => '../../config/app.php']
        );

        $this->get($signedUrl)->assertNotFound();
    }

    public function test_admin_qr_route_requires_an_admin(): void
    {
        $this->writePrivateFile('customizations/qr_codes/owned.png', 'owned-bytes');

        $this->get(route('admin.orders.qr.show', ['path' => 'customizations/qr_codes/owned.png']))
            ->assertRedirect(route('login'));

        $this->actingAs($this->customer())
            ->get(route('admin.orders.qr.show', ['path' => 'customizations/qr_codes/owned.png']))
            ->assertForbidden();
    }

    public function test_admin_qr_route_serves_only_order_owned_paths(): void
    {
        $admin = $this->admin();
        $this->writePrivateFile('customizations/qr_codes/bound.png', 'bound-bytes');

        // Bind the path to a real OrderItem snapshot (the only legal owner).
        $order = new Order([
            'customer_name' => 'مشتری نمونه',
            'customer_phone' => $admin->phone,
            'shipping_address' => 'تهران، خیابان ولیعصر',
            'shipping_postal_code' => '1234567890',
        ]);
        $order->user_id = $admin->id;
        $order->total_price = 500000;
        $order->reference = 'ORD-'.now()->year.'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $order->token = Str::random(40);
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name_snapshot' => 'کارت فلزی کلاسیک',
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'qr_code_enabled' => true,
                'qr_code_path' => 'customizations/qr_codes/bound.png',
            ],
        ]);

        // Bound path -> served to the admin.
        $response = $this->actingAs($admin)
            ->get(route('admin.orders.qr.show', ['path' => 'customizations/qr_codes/bound.png']));
        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');

        ob_start();
        $response->baseResponse->sendContent();
        $this->assertStringContainsString('bound-bytes', (string) ob_get_clean());

        // A valid *file* that exists on disk but is bound to NO order item:
        // the ownership check must refuse to serve it (arbitrary/foreign path).
        $this->writePrivateFile('customizations/qr_codes/foreign.png', 'foreign-bytes');
        $this->actingAs($admin)
            ->get(route('admin.orders.qr.show', ['path' => 'customizations/qr_codes/foreign.png']))
            ->assertNotFound();

        // Traversal is refused at the ownership layer as well.
        $this->actingAs($admin)
            ->get(route('admin.orders.qr.show', ['path' => '../../config/database.php']))
            ->assertNotFound();
    }

    public function test_qr_upload_rejects_invalid_files(): void
    {
        Storage::fake('local');

        // Wrong mime: not in the whitelist (png/jpg/jpeg/webp/svg).
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('qr_code_enabled', true)
            ->set('qr_code_file', UploadedFile::fake()->create('qr.pdf', 10))
            ->assertHasErrors('qr_code_file');

        // Oversized: over 2 MB.
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('qr_code_enabled', true)
            ->set('qr_code_file', UploadedFile::fake()->image('big.png', 1200, 1200)->size(4096))
            ->assertHasErrors('qr_code_file');
    }

    public function test_replacing_qr_deletes_the_previous_private_file(): void
    {
        Storage::fake('local');

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $component->set('qr_code_enabled', true)
            ->set('qr_code_file', UploadedFile::fake()->image('first.png', 200, 200));

        $first = $component->get('qr_code_path');
        Storage::disk('local')->assertExists($first);

        $component->set('qr_code_enabled', true)
            ->set('qr_code_file', UploadedFile::fake()->image('second.png', 200, 200));

        $second = $component->get('qr_code_path');

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_remove_qr_code_deletes_the_private_file(): void
    {
        Storage::fake('local');

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('qr_code_enabled', true)
            ->set('qr_code_file', UploadedFile::fake()->image('qr.png', 200, 200));

        $path = $component->get('qr_code_path');
        Storage::disk('local')->assertExists($path);

        $component->call('removeQrCode')
            ->assertSet('qr_code_path', null)
            ->assertSet('qr_code_file', null)
            ->assertSet('qr_code_enabled', false);

        Storage::disk('local')->assertMissing($path);
    }

    private function writePrivateFile(string $path, string $contents): void
    {
        Storage::disk('local')->put($path, $contents);
    }
}
