# برنامه اقدامات اصلاحی و مقاوم‌سازی امنیتی (Security Remediation Plan)
**پروژه:** سامانه ElvaCard  
**تاریخ تهیه:** ۱۰ اکتبر ۲۰۲۶  

این سند بر اساس اولویت‌بندی ریسک (Risk-Driven Prioritization) تدوین شده است تا قبل از انتشار نهایی سامانه، بدون ایجاد اختلال در کارکرد برنامه یا تغییر در قراردادهای تجاری، بالاترین سطح محافظت اعمال گردد.

---

## ۱. اقدامات با اولویت بالا (مرحله استقرار پروداکشن — Pre-Release Mandatory Hardening)

این موارد به دلیل عدم تغییر در کد برنامه، در زمان استقرار روی هاست اصلی در فایل `.env` پروداکشن تنظیم می‌شوند:

### اقدام ۱.۱: خاموش کردن حالت دیباگ و تنظیم محیط عملیاتی
* **هدف رفع:** یافته `SEC-003`
* **تنظیمات در `.env` هاست اصلی:**
  ```env
  APP_ENV=production
  APP_DEBUG=false
  ```
* **تأییدیه و آزمون بازگشتی:** ایجاد عمدی یک خطای ۴۰۴ و ۵۰۰ در محیط استقرار و اطمینان از نمایش ویوی خطای اختصاصی زیبا به جای استک‌تریس سیستم.

### اقدام ۱.۲: فعال‌سازی الزام فلگ Secure برای کوکی‌های سشن و احراز هویت
* **هدف رفع:** یافته `SEC-004`
* **پیش‌نیاز:** فعال‌سازی گواهی SSL (HTTPS) روی دامنه اصلی سایت.
* **تنظیمات در `.env` هاست اصلی:**
  ```env
  SESSION_SECURE_COOKIE=true
  ```
* **تأییدیه:** بررسی کوکی‌های `laravel-session` و `XSRF-TOKEN` در مرورگر و اطمینان از وجود پرچم `Secure; HttpOnly; SameSite=Lax`.

### اقدام ۱.۳: مخفی‌سازی بنر نگارش PHP
* **هدف رفع:** یافته `SEC-002`
* **تنظیمات در سرور:** قرار دادن دستور زیر در `php.ini` سرور یا پیکربندی وب‌سرور Nginx/LiteSpeed:
  ```ini
  expose_php = Off
  ```
* **تأییدیه:** ارسال درخواست با cURL و اطمینان از حذف کامل هدر `X-Powered-By`.

---

## ۲. اقدامات با اولویت متوسط (پیشنهاد کدنویسی در لایه میان‌افزار — Application Middleware Hardening)

> **توجه:** طبق قوانین اکید Stage 24.0، کدهای این بخش فعلاً **اعمال نشده‌اند** و پس از تأیید و صدور مجوز کاربر قابل پیاده‌سازی خواهند بود.

### اقدام ۲.۱: اضافه کردن میان‌افزار هدرهای امنیتی (SetSecurityHeaders Middleware)
* **هدف رفع:** یافته `SEC-001` (محافظت قطعی در برابر Clickjacking و فریب محتوا)
* **کد پیشنهادی برای ایجاد `app/Http/Middleware/SetSecurityHeaders.php`:**
  ```php
  <?php

  namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;

  class SetSecurityHeaders
  {
      public function handle(Request $request, Closure $next): Response
      {
          $response = $next($request);

          $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
          $response->headers->set('X-Content-Type-Options', 'nosniff');
          $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
          $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

          return $response;
      }
  }
  ```
* **ثبت در `bootstrap/app.php`:**
  افزودن کلاس فوق به گروه میان‌افزارهای `web`.
* **تست‌های رگرسیون موردنیاز:**
  اجرای مجدد `tests/Feature/SecurityAuditInvariantsTest.php` برای اطمینان از حضور هدرهای فوق در تمامی پاسخ‌ها.

---

## ۳. اقدامات با اولویت پایین (بدهی‌های فنی و به‌روزرسانی ابزارها)

### اقدام ۳.۱: به‌روزرسانی ابزارهای بیلد فرانت‌اند
* **هدف رفع:** یافته `SEC-005`
* **دستور پس از انتشار:**
  ```bash
  npm update
  ```
  جهت اصلاح وابستگی‌های ابزار بیلد بدون تغییر در استایل‌های کامپایل‌شده پروژه.

---

## ۴. ماتریس ردیابی و آزمون‌های اعتبارسنجی (Regression Testing Matrix)

| اقدام اصلاحی | تست خودکار اعتبارسنجی | شاخص موفقیت (Success Criteria) |
| :--- | :--- | :--- |
| پیاده‌سازی هدرهای امنیتی | `SecurityAuditInvariantsTest` | دریافت هدرهای `X-Frame-Options` و `nosniff` در خروجی پاسخ |
| ممانعت از Clickjacking | تست در مرورگر واقعی (Iframe Embedding) | مسدود شدن نمایش سایت داخل دامنه متفرقه توسط مرورگر |
| مخفی‌سازی خطاها | تست دستی با پارامتر نامعتبر | عدم مشاهده استک تریس در صفحه خطای ۵۰۰ |
| کوکی امن | DevTools -> Application -> Cookies | مشاهده فلگ تیک خورده `Secure` برای کوکی‌های نشست |
