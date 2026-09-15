<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DesignImageManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DesignImageManagerOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(): CateDesign
    {
        return CateDesign::create([
            'name' => 'گزینه‌ها',
            'slug' => 'options-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(string $name, string $hex = '#FF0000'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => $hex,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function design(string $name, string $slug, bool $active = true): Design
    {
        return Design::create([
            'cate_design_id' => $this->category()->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function image(Design $design, Color $color, string $path): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function queriesFor(array $log, array $tables): array
    {
        return array_values(array_filter(
            array_map(fn (array $query) => $query['query'], $log),
            fn (string $sql) => (bool) preg_match('~from (`|")(\w+)(`|")~i', $sql, $m) && in_array($m[2], $tables, true)
        ));
    }

    public function test_render_queries_design_and_color_options_exactly_once(): void
    {
        $design = $this->design('طرح اصلی', 'main-design');
        $color = $this->color('بنفش', '#800080');
        $this->image($design, $color, 'designs/main.png');

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->assertSee('طرح اصلی')
            ->assertSee('بنفش');

        $designQueries = $this->queriesFor(DB::getQueryLog(), ['designs']);
        $colorQueries = $this->queriesFor(DB::getQueryLog(), ['colors']);

        $this->assertCount(2, $designQueries, 'Design table queried once for options plus once for the image list eager load.');
        $this->assertCount(2, $colorQueries, 'Color table queried once for options plus once for the image list eager load.');
    }

    public function test_design_filter_filters_image_list(): void
    {
        $first = $this->design('طرح یک', 'first-design');
        $second = $this->design('طرح دو', 'second-design');
        $color = $this->color('قرمز');
        $this->image($first, $color, 'designs/one.png');
        $this->image($second, $color, 'designs/two.png');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->assertSee('designs/one.png')
            ->assertSee('designs/two.png')
            ->set('designFilter', $first->id)
            ->assertSee('designs/one.png')
            ->assertDontSee('designs/two.png');
    }

    public function test_edit_brings_current_inactive_design_into_options(): void
    {
        $this->design('طرح فعال', 'active-design');
        $inactive = $this->design('طرح غیرفعال', 'inactive-design', false);
        $color = $this->color('آبی', '#0000FF');
        $image = $this->image($inactive, $color, 'designs/inactive.png');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->assertSee('طرح فعال')
            ->assertDontSee('طرح غیرفعال (غیرفعال)')
            ->call('edit', $image->id)
            ->assertSet('editingId', $image->id)
            ->assertSee('طرح غیرفعال (غیرفعال)');
    }
}
