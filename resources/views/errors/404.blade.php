<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>صفحه مورد نظر یافت نشد - الواکارت</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-6">
        <div class="max-w-md w-full text-center">
            <p class="text-7xl font-bold text-primary-600">۴۰۴</p>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">صفحه مورد نظر یافت نشد</h1>
            <p class="mt-2 text-sm text-gray-600">صفحه‌ای که به دنبال آن هستید وجود ندارد یا منتقل شده است.</p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 transition">بازگشت به صفحه اصلی</a>
                <a href="{{ route('catalog.products.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">مشاهده محصولات</a>
            </div>
        </div>
    </div>
</body>
</html>