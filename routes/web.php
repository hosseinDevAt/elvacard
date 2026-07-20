<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/طراحی/کارت-بانکی', \App\Livewire\Designer\BankCardDesigner::class)->name('designer.bank');
Route::get('/طراحی/کارت-سوخت', \App\Livewire\Designer\FuelCardDesigner::class)->name('designer.fuel');

Route::get('/ورود', function () {
    $user = \App\Models\User::firstOrCreate(
        ['phone' => '09000000000'],
        ['name' => 'ادمین', 'password' => bcrypt('123456')]
    );
    Auth::login($user);
    return redirect()->route('home');
})->name('login');

Route::post('/خروج', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

Route::get('/پنل-کاربری', function () {
    return view('dashboard');
})->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\AutoLoginAdmin::class)->group(function () {
    Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/رنگ‌ها', \App\Livewire\Admin\ColorManager::class)->name('colors');
    Route::get('/دسته‌بندی-طرح‌ها', \App\Livewire\Admin\CateDesignManager::class)->name('cate-designs');
    Route::get('/انواع-کارت', \App\Livewire\Admin\CardTypeManager::class)->name('card-types');
    Route::get('/سفارشات', \App\Livewire\Admin\OrderManager::class)->name('orders');
    Route::get('/کاربران', \App\Livewire\Admin\UserManager::class)->name('users');
});
