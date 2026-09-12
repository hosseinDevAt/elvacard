<?php

use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Catalog\ProductCatalogController;
use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Checkout\ManualTransferPaymentController;
use App\Http\Controllers\Cms\ArticleController;
use App\Http\Controllers\Cms\FaqController;
use App\Http\Controllers\Cms\HomepageController;
use App\Http\Controllers\Cms\PageController;
use App\Http\Controllers\Order\OrderHistoryController;
use App\Http\Controllers\Order\OrderTrackingController;
use App\Http\Controllers\Admin\PaymentReceiptController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppearanceManager;
use App\Livewire\Admin\ArticleCategoryManager;
use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\CateDesignManager;
use App\Livewire\Admin\ColorManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DesignColorCompatibilityManager;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\FaqItemManager;
use App\Livewire\Admin\HomepageSectionManager;
use App\Livewire\Admin\MenuItemManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\OrderManager;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\SiteSettingManager;
use App\Livewire\Admin\UserManager;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomepageController::class, 'index'])->name('home');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::get('/dashboard/colors', ColorManager::class)->name('admin.colors');
    Route::get('/dashboard/products', ProductManager::class)->name('admin.products');
    Route::get('/dashboard/product-colors', ProductColorPriceManager::class)->name('admin.product-colors');
    Route::get('/dashboard/designs', DesignManager::class)->name('admin.designs');
    Route::get('/dashboard/designs/create', DesignWizard::class)->name('admin.designs.create');
    Route::get('/dashboard/designs/{designId}/edit', DesignWizard::class)->name('admin.designs.edit');
    Route::get('/dashboard/design-images', DesignImageManager::class)->name('admin.design-images');
    Route::get('/dashboard/design-color-compatibilities', DesignColorCompatibilityManager::class)->name('admin.design-color-compatibilities');
    Route::get('/dashboard/cate-designs', CateDesignManager::class)->name('admin.cate-designs');
    Route::get('/dashboard/faq', FaqItemManager::class)->name('admin.faq');
    Route::get('/dashboard/announcements', AnnouncementManager::class)->name('admin.announcements');
    Route::get('/dashboard/article-categories', ArticleCategoryManager::class)->name('admin.article-categories');
    Route::get('/dashboard/articles', ArticleManager::class)->name('admin.articles');
    Route::get('/dashboard/pages', PageManager::class)->name('admin.pages');
    Route::get('/dashboard/menus', MenuManager::class)->name('admin.menus');
    Route::get('/dashboard/menu-items', MenuItemManager::class)->name('admin.menu-items');
    Route::get('/dashboard/site-settings', SiteSettingManager::class)->name('admin.site-settings');
    Route::get('/dashboard/appearance', AppearanceManager::class)->name('admin.appearance');
    Route::get('/dashboard/homepage-sections', HomepageSectionManager::class)->name('admin.homepage-sections');
    Route::get('/dashboard/orders', OrderManager::class)->name('admin.orders');
    Route::get('/dashboard/orders/{order}/payments/{payment}/receipt', [PaymentReceiptController::class, 'show'])
        ->name('admin.payments.receipt');
    Route::get('/dashboard/users', UserManager::class)->name('admin.users');
});

Route::get('/catalog/products', [ProductCatalogController::class, 'index'])->name('catalog.products.index');
Route::get('/catalog/products/{slug}', [ProductCatalogController::class, 'show'])->name('catalog.products.show');
Route::get('/catalog/designs', [ProductCatalogController::class, 'designCatalog'])->name('catalog.designs.index');
Route::get('/design', [ProductCatalogController::class, 'designLanding'])->name('custom-card.design');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/empty', [CartController::class, 'empty'])->name('cart.empty');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/checkout/success/{token}', [CheckoutController::class, 'success'])
    ->where('token', '[A-Za-z0-9]{16,64}')
    ->name('checkout.success');

Route::get('/checkout/payment/{order:token}', [ManualTransferPaymentController::class, 'show'])
    ->where('order', '[A-Za-z0-9]{16,64}')
    ->name('checkout.payment');
Route::post('/checkout/payment/{order:token}', [ManualTransferPaymentController::class, 'store'])
    ->where('order', '[A-Za-z0-9]{16,64}')
    ->middleware('throttle:10,1')
    ->name('checkout.payment.store');

Route::get('/order-tracking', [OrderTrackingController::class, 'create'])->name('order-tracking.index');
Route::post('/order-tracking', [OrderTrackingController::class, 'store'])->middleware('throttle:5,1')->name('order-tracking.check');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');

Route::middleware('auth')->group(function () {
    Route::get('/account', CustomerDashboardController::class)->name('account.dashboard');

    Route::get('/orders', [OrderHistoryController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderHistoryController::class, 'show'])->name('orders.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
