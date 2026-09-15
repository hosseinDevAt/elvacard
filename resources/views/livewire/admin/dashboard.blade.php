<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">داشبورد</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">کاربران</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($totalUsers) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">کل سفارشات</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($totalOrders) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">سفارشات در انتظار</div>
            <div class="text-2xl font-bold text-amber-600">{{ number_format($pendingOrders) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">کل محصولات</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($totalProducts) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">پرداخت‌های در انتظار بررسی</div>
            <div class="text-2xl font-bold text-amber-600">{{ number_format($pendingReviewPayments) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">پرداخت‌های موفق</div>
            <div class="text-2xl font-bold text-green-700">{{ number_format($successfulPayments) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500 mb-1">مجموع درآمد (پرداخت‌های موفق)</div>
            <div class="text-2xl font-bold text-gray-900 font-mono">{{ number_format($totalRevenue) }} تومان</div>
        </div>
    </div>

    <div class="space-y-8">
        {{-- فروشگاه --}}
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-3">فروشگاه</h2>
            <div class="grid lg:grid-cols-3 gap-4">
                <a href="{{ route('admin.products') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">مدیریت محصولات</h3>
                    <p class="text-sm text-gray-500">محصولات، وضعیت و اطلاعات اصلی</p>
                </a>
                <a href="{{ route('admin.colors') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">مدیریت رنگ‌ها</h3>
                    <p class="text-sm text-gray-500">اضافه، ویرایش و حذف رنگ‌ها</p>
                </a>
                <a href="{{ route('admin.designs') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">مدیریت طرح‌ها</h3>
                    <p class="text-sm text-gray-500">اطلاعات، تصاویر، رنگ‌ها و سازگاری در یک Workflow</p>
                </a>
            </div>
        </div>

        {{-- محتوا --}}
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-3">محتوا</h2>
            <div class="grid lg:grid-cols-4 gap-4">
                <a href="{{ route('admin.pages') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">صفحات</h3>
                    <p class="text-sm text-gray-500">مدیریت صفحات سایت</p>
                </a>
                <a href="{{ route('admin.articles') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">مقالات</h3>
                    <p class="text-sm text-gray-500">مدیریت و انتشار مقالات</p>
                </a>
                <a href="{{ route('admin.article-categories') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">دسته‌بندی مقالات</h3>
                    <p class="text-sm text-gray-500">مدیریت دسته‌بندی‌های مقالات</p>
                </a>
                <a href="{{ route('admin.faq') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">سوالات متداول</h3>
                    <p class="text-sm text-gray-500">مدیریت پرسش و پاسخ‌ها</p>
                </a>
                <a href="{{ route('admin.announcements') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">اطلاعیه‌ها</h3>
                    <p class="text-sm text-gray-500">نمایش اطلاعیه‌های سایت</p>
                </a>
            </div>
        </div>

        {{-- ظاهر سایت --}}
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-3">ظاهر سایت</h2>
            <div class="grid lg:grid-cols-4 gap-4">
                <a href="{{ route('admin.appearance') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">ظاهر و برند</h3>
                    <p class="text-sm text-gray-500">هویت، بنرها و آیکون‌ها</p>
                </a>
                <a href="{{ route('admin.menus') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">منوها</h3>
                    <p class="text-sm text-gray-500">مدیریت منوهای سایت</p>
                </a>
                <a href="{{ route('admin.menu-items') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">آیتم‌های منو</h3>
                    <p class="text-sm text-gray-500">مدیریت آیتم‌های هر منو</p>
                </a>
                <a href="{{ route('admin.homepage-sections') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                    <h3 class="font-bold text-gray-900 mb-1">صفحه اصلی</h3>
                    <p class="text-sm text-gray-500">مدیریت بخش‌های صفحه اصلی</p>
                </a>
            </div>
        </div>

        {{-- سفارشات / کاربران / تنظیمات --}}
        <div class="grid lg:grid-cols-4 gap-4">
            <a href="{{ route('admin.orders') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                <h3 class="font-bold text-gray-900 mb-1">مدیریت سفارشات</h3>
                <p class="text-sm text-gray-500">مشاهده، پرداخت‌ها و تغییر وضعیت سفارشات</p>
            </a>
            <a href="{{ route('admin.payments') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                <h3 class="font-bold text-gray-900 mb-1">مدیریت پرداخت‌ها</h3>
                <p class="text-sm text-gray-500">مشاهده تمام پرداخت‌ها، بررسی و رد/تأیید</p>
            </a>
            <a href="{{ route('admin.users') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                <h3 class="font-bold text-gray-900 mb-1">کاربران</h3>
                <p class="text-sm text-gray-500">مشاهده کاربران و تعداد سفارشات</p>
            </a>
        </div>
            <a href="{{ route('admin.site-settings') }}" wire:navigate class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
                <h3 class="font-bold text-gray-900 mb-1">تنظیمات سایت</h3>
                <p class="text-sm text-gray-500">مدیریت تنظیمات عمومی سایت</p>
            </a>
        </div>
    </div>
</div>
