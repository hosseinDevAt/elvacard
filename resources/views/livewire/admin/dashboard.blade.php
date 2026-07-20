<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">داشبورد</h1>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
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
            <div class="text-sm text-gray-500 mb-1">انواع کارت</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($totalCardTypes) }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <a href="{{ route('admin.colors') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت رنگ‌ها</h3>
            <p class="text-sm text-gray-500">اضافه، ویرایش و حذف رنگ‌ها</p>
        </a>
        <a href="{{ route('admin.cate-designs') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت دسته‌بندی طرح‌ها</h3>
            <p class="text-sm text-gray-500">مدیریت دسته‌بندی‌ها و گروه‌های طرح</p>
        </a>
        <a href="{{ route('admin.card-types') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت انواع کارت</h3>
            <p class="text-sm text-gray-500">رنگ‌ها و قیمت‌های کارت</p>
        </a>
        <a href="{{ route('admin.orders') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition">
            <h3 class="font-bold text-gray-900 mb-1">مدیریت سفارشات</h3>
            <p class="text-sm text-gray-500">مشاهده و مدیریت سفارشات</p>
        </a>
    </div>
</div>
