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

    <div class="grid lg:grid-cols-2 gap-6">
        <a href="{{ route('admin.colors') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت رنگ‌ها</h3>
            <p class="text-sm text-gray-500">اضافه، ویرایش و حذف رنگ‌ها</p>
        </a>
        <a href="{{ route('admin.products') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت محصولات</h3>
            <p class="text-sm text-gray-500">محصولات، وضعیت و اطلاعات اصلی</p>
        </a>
        <a href="{{ route('admin.product-colors') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">قیمت رنگ محصولات</h3>
            <p class="text-sm text-gray-500">قیمت‌گذاری هر محصول بر اساس رنگ</p>
        </a>
        <a href="{{ route('admin.cate-designs') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت دسته‌بندی طرح‌ها</h3>
            <p class="text-sm text-gray-500">مدیریت دسته‌بندی‌ها و گروه‌های طرح</p>
        </a>
        <a href="{{ route('admin.designs') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت طرح‌ها</h3>
            <p class="text-sm text-gray-500">اضافه، ویرایش و حذف طرح‌ها</p>
        </a>
        <a href="{{ route('admin.design-images') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">تصاویر طرح‌ها</h3>
            <p class="text-sm text-gray-500">مدیریت تصاویر هر طرح و رنگ آن</p>
        </a>
        <a href="{{ route('admin.design-color-compatibilities') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">سازگاری رنگ طرح‌ها</h3>
            <p class="text-sm text-gray-500">انتخاب رنگ‌های مجاز برای هر تصویر</p>
        </a>
        <a href="{{ route('admin.article-categories') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">دسته‌بندی مقالات</h3>
            <p class="text-sm text-gray-500">مدیریت دسته‌بندی‌های مقالات</p>
        </a>
        <a href="{{ route('admin.articles') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مقالات</h3>
            <p class="text-sm text-gray-500">مدیریت و انتشار مقالات</p>
        </a>
        <a href="{{ route('admin.pages') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">صفحات</h3>
            <p class="text-sm text-gray-500">مدیریت صفحات سایت</p>
        </a>
        <a href="{{ route('admin.menus') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">منوها</h3>
            <p class="text-sm text-gray-500">مدیریت منوها و آیتم‌های آن</p>
        </a>
        <a href="{{ route('admin.homepage-sections') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">صفحه اصلی</h3>
            <p class="text-sm text-gray-500">مدیریت بخش‌های صفحه اصلی</p>
        </a>
        <a href="{{ route('admin.site-settings') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">تنظیمات سایت</h3>
            <p class="text-sm text-gray-500">مدیریت تنظیمات عمومی سایت</p>
        </a>
        <a href="{{ route('admin.orders') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت سفارشات</h3>
            <p class="text-sm text-gray-500">مشاهده و مدیریت سفارشات</p>
        </a>
    </div>
</div>
