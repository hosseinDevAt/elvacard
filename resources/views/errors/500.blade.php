<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.favicon-links')
    <title>خطای داخلی سرور - {{ site_setting('site_name', 'الواکارت') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background: #f8fafc; color: #010619; margin: 0; padding: 20px; }
        .wrap { min-height: 90vh; display: flex; align-items: center; justify-content: center; }
        .box { max-width: 440px; width: 100%; text-align: center; }
        .badge { display: inline-flex; width: 90px; height: 90px; border-radius: 24px; background: #010619; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 10px 25px -5px rgba(1,6,25,0.2); }
        .code { font-size: 38px; font-weight: 900; color: #ffde5b; margin: 0; line-height: 1; font-family: monospace; }
        h1 { font-size: 24px; font-weight: 900; margin: 0 0 10px; color: #010619; }
        p { font-size: 13px; color: #64748b; line-height: 1.8; margin: 0 0 24px; }
        a { display: inline-flex; align-items: center; justify-content: center; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; color: #010619; background: #ffde5b; box-shadow: 0 4px 12px rgba(255,222,91,0.25); transition: background 0.2s; }
        a:hover { background: #f5d347; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="box">
            <div class="badge">
                <p class="code">۵۰۰</p>
            </div>
            <h1>خطای داخلی سرور</h1>
            <p>مشکلی در اجرای این صفحه به وجود آمده است. لطفاً بعداً دوباره تلاش کنید.</p>
            <a href="{{ route('home') }}">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</body>
</html>