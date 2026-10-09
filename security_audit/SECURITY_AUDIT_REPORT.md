# گزارش ممیزی جامع امنیتی و آزمون نفوذ کنترل‌شده (Security Audit Report)
**پروژه:** سامانه فروشگاه و شخصی‌سازی کارت‌های هوشمند ElvaCard  
**محیط آزمون:** `C:\laragon\www\card-project` | Branch: `master`  
**تاریخ ممیزی:** ۱۰ اکتبر ۲۰۲۶ (۱۹ مهر ۱۴۰۵)  
**تیم ارزیابی:** Senior Application Security Engineer, Laravel Security Auditor & Penetration Tester  
**وضعیت کلی امنیت (Overall Security Status):** **MODERATE RISK (ریسک متوسط به دلیل تنظیمات محیط توسعه/سرور — آماده انتشار پس از اعمال Hardening)**

---

## ۱. خلاصه مدیریتی (Executive Summary)

این ارزیابی امنیتی جامع و آزمون نفوذ کنترل‌شده بر روی تمامی لایه‌های معماری نرم‌افزار ElvaCard شامل لایه مسیریابی و کنترل دسترسی، سازوکار احراز هویت و مدیریت نشست (Session)، آزمون جعل هویت و دسترسی به منابع اشخاص دیگر (IDOR/BOLA)، بررسی آسیب‌پذیری‌های تزریق (SQLi, XSS, Command Injection, Path Traversal)، کنترل آپلود فایل‌ها، امنیت کامپوننت‌های Livewire، منطق تجاری سبد خرید و قیمت‌گذاری و تنظیمات هدرهای HTTP انجام شد.

### نتایج کلیدی:
1. **هسته نرم‌افزاری و منطق تجاری فوق‌العاده مستحکم:** هیچ‌گونه آسیب‌پذیری بحرانی (Critical) نظیر SQL Injection، دسترسی غیرمجاز به پایگاه داده، جعل قیمت، دور زدن اعتبارسنجی درگاه پرداخت یا دور زدن مالکیت سفارشات (IDOR) در سامانه مشاهده نشد.
2. **یکپارچگی احراز هویت و سطوح دسترسی ادمین:** تمامی ۲۷ کامپوننت Livewire پنل مدیریت دارای تریت دفاعی `AuthorizesAdminActions` بوده و درخواست‌های فاقد مجوز را در چرخه حیات کامپوننت با خطای ۴۰۳ مسدود می‌کنند.
3. **مکانیزم دفاع در عمق آپلود فایل:** آپلود تصاویر دارای اعتبارسنجی جادویی بایت‌های تصویر (`getimagesize`)، پسوند مجاز، نام‌گذاری تصادفی با توکن ۴۰ کاراکتری و ذخیره‌سازی در دیسک خصوصی (`local`) بوده و از Path Traversal جلوگیری می‌کند.
4. **نقاط نیازمند مقاوم‌سازی (Hardening Requirements):** ۵ مورد امنیتی با درجه اهمیت متوسط و پایین (فاقد آسیب‌پذیری با اکسپلویت مستقیم در کد) شناسایی شدند که عمدتاً مربوط به هدرهای امنیتی HTTP (`X-Frame-Options`، `X-Content-Type-Options`)، افشای بنر نسخه PHP (`X-Powered-By`) و لزوم تغییر متغیرهای `.env` در محیط پروداکشن (`APP_DEBUG=false` و `SESSION_SECURE_COOKIE=true`) هستند.

---

## ۲. محدوده و روش‌شناسی ارزیابی (Scope & Methodology)

### دامنه بررسی (Scope):
* تمام Routeهای عمومی فروشگاه، طراح کارت بانکی (`/design/bank`)، طراح کارت سوخت (`/design/fuel`)، سبد خرید و تسویه‌حساب (`/checkout`).
* فرآیند پیگیری سفارشات (`/order-tracking`) و نمایش اطلاعات سفارش.
* کنترلرهای پرداخت دستی و درگاه آنلاین (`ManualTransferPaymentController`, `GatewayPaymentController`, `GatewayPaymentCore`).
* تمامی مسیرهای احراز هویت و پنل مدیریت (`/dashboard/*`).
* وابستگی‌های Composer و NPM.
* سرویس‌های اعتبارسنجی آپلود، ضد جعل URL و ضد XSS در فایل‌های SVG (`SvgSanitizer`).

### روش‌شناسی (Methodology):
* **تحلیل ایستای کد (SAST):** بازبینی کامل الگوهای امنیتی، Eloquent Mass Assignment، Raw Queries و تگ‌های خروجی Blade.
* **تحلیل پویای برنامه (DAST):** اجرای آزمون‌های کنترل‌شده با مرورگر واقعی و ارسال پی‌لودهای بی‌خطر جهت بررسی رفتارهای بازتابی (Reflected XSS)، پیمایش دایرکتوری (Path Traversal) و دور زدن نرخ مجاز درخواست (Rate Limiting).
* **تست‌های واحد و ویژگی ایزوله (PHPUnit):** اجرای آزمون‌های امنیتی در بستر ایزوله حافظه موقت SQLite (`:memory:`) بدون کوچک‌ترین تغییر یا نشت به پایگاه داده اصلی.

---

## ۳. محدودیت‌های آزمون (Limitations & Untested Areas)

با رعایت کامل اصول ایمنی و دستورات کاربر:
1. هیچ‌گونه تراکنش مالی واقعی در شبکه بانکی شاپرک انجام نشد (ایزولاسیون مالی).
2. هیچ پیامک واقعی به شماره تلفن‌های مشتریان ارسال نشد.
3. حملات منع سرویس (DoS)، Password Spraying یا Brute-force تهاجمی اجرا نشد (بررسی نرخ درخواست‌ها صرفاً با ۶ درخواست آزمایشی کنترل‌شده انجام گرفت).
4. فایل `.env` و پایگاه داده اصلی دستکاری نشدند.

---

## ۴. اینونتوری سطوح حمله و مسیرها (Attack Surface Inventory)

| دسته‌بندی سطح حمله | مسیرها / نقاط ورودی | میان‌افزارها و سپرهای دفاعی | ارزیابی امنیت |
| :--- | :--- | :--- | :--- |
| **استورفرانت عمومی** | `/`, `/catalog/*`, `/articles/*`, `/faq`, `/order-process` | `web` | امن (کدگذاری خودکار HTML در Blade) |
| **طراح‌های هوشمند** | `/design/bank`, `/design/fuel` | `web`, Livewire Lifecycle, Luhn Rule | امن (اعتبارسنجی سرور برای رنگ، طرح، ابعاد) |
| **سبد خرید و تسویه** | `/cart/*`, `/checkout` | `web`, `throttle:10,1`, Token Idempotency | امن (محاسبه مجدد قیمت از کاتالوگ دیتابیس) |
| **پیگیری سفارشات** | `/order-tracking` (POST) | `web`, `throttle:5,1`, Dual-Factor (Ref + Last4 Phone) | امن (مسدودی Brute-force، عدم افشای PII) |
| **آپلود طرح مشتری** | Livewire Temp Upload, Preview Route | Mime/Magic Bytes, Regex Token, Local Disk Jail | امن (جلوگیری کامل از Path Traversal) |
| **پرداخت دستی** | `/checkout/payment/{order:token}` | Token Binding, Image Mime Whitelist, Local Storage | امن (ذخیره در پوشه غیرعمومی) |
| **کال‌بک درگاه بانکی** | `/checkout/payment/callback/{gateway}` | `throttle:10,1`, Amount Verification, DB Row Lock | امن (استعلام مجدد از وب‌سرویس بانک) |
| **احراز هویت** | `/login`, `/logout` | `guest`, `auth`, RateLimiter (5/120s), Regnerate Session | امن (پاسخ خطای یکنواخت، بدون Enumeration) |
| **پنل مدیریت (۲۷ بخش)** | `/dashboard/*` | `auth`, `admin`, `AuthorizesAdminActions` Trait | امن (مسدودی ۴۰۳ در صورت عدم دسترسی ادمین) |

---

## ۵. شواهد آزمون‌های خودکار و مرورگر (Observed Evidence)

1. **تأیید ایزولاسیون دیتابیس تست:**
   - تست `DatabaseConnectionSafetyTest` با ۵ Assertion تأیید کرد درایور تست `sqlite` در حافظه موقت بوده و دیتابیس اصلی دست‌نخورده است.
2. **تست‌های رگرسیون و دفاعی امنیت:**
   - سوئیت `SecurityAuditInvariantsTest`: ۶ آزمون با ۱۷ Assertion (رد پیمایش دایرکتوری، رد آپلود بدون امضا، مالکیت سفارش، لیمیتر پیگیری، کنترل نقش ادمین و وضعیت هدرها) ۱۰۰٪ Pass شدند.
   - سوئیت `RateLimitingVerificationTest` و `AdminPanelAccessTest`: ۲۶ آزمون با ۱۳۲ Assertion ۱۰۰٪ Pass شدند.
3. **تست تعاملی با مرورگر واقعی:**
   - تزریق پروب XSS در کاتالوگ و پیگیری سفارش: پروب‌ها در DOM به صورت متن امن انکود شده و هیچگونه اجرای جاوااسکریپت یا آلرت رخ نداد (`01_storefront_xss_probe.png` و `02_order_tracking_xss_probe.png`).
   - تلاش برای ورود مستقیم به آدرس‌های پنل ادمین توسط کاربر غیرلاگین: بلافاصله به صفحه لاگین ریدایرکت شد (`03_direct_admin_access_blocked.png`).
