<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>خطای داخلی - الواکارت</title>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; background: #f9fafb; color: #111827; margin: 0; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 0 24px; }
        .box { max-width: 480px; width: 100%; text-align: center; }
        h1 { font-size: 26px; margin: 16px 0 8px; }
        p { font-size: 14px; color: #4b5563; line-height: 1.8; }
        a { display: inline-block; margin-top: 20px; padding: 10px 22px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; color: #fff; background: #00071a; }
        a:hover { background: #0b1c33; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="box">
            <p style="font-size:72px;font-weight:700;color:#ffde5b;margin:0;line-height:1">۵۰۰</p>
            <h1>خطای داخلی سرور</h1>
            <p>مشکلی در اجرای این صفحه به وجود آمده است. لطفاً بعداً دوباره تلاش کنید.</p>
            <a href="{{ route('home') }}">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</body>
</html>