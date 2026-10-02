<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon-links')
    <title>صفحه مورد نظر یافت نشد - {{ site_setting('site_name', 'الواکارت') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 selection:bg-[#ffde5b] selection:text-[#010619]">
    <div class="max-w-md w-full text-center">
        <div class="relative mx-auto mb-6 flex h-28 w-28 items-center justify-center rounded-3xl bg-[#010619] shadow-xl shadow-[#010619]/20">
            <span class="text-4xl font-black text-[#ffde5b] font-mono">۴۰۴</span>
            <div class="pointer-events-none absolute -right-3 -top-3 h-10 w-10 rounded-full bg-[#ffde5b]/20 blur-md"></div>
        </div>

        <h1 class="text-2xl font-black text-[#010619] sm:text-3xl">صفحه مورد نظر یافت نشد</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">صفحه‌ای که به دنبال آن هستید وجود ندارد، حذف شده یا نشانی آن تغییر کرده است.</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-brand-primary px-6 py-3 text-xs font-bold shadow-md shadow-[#ffde5b]/20 hover:scale-[1.02] active:scale-[0.98]">
                بازگشت به صفحه اصلی
            </a>
            <a href="{{ route('catalog.products.index') }}" class="btn-brand-secondary px-6 py-3 text-xs font-bold hover:bg-slate-50">
                مشاهده محصولات فروشگاه
            </a>
        </div>
    </div>
</body>
</html>