# PROJECT_CONTEXT.md

> این فایل سند مرجع اصلی پروژه است.
>
> **تمام AI Agentها و توسعه‌دهندگان باید قبل از هرگونه تغییر در پروژه، این فایل را مطالعه کنند.**
>
> این سند مشخص می‌کند پروژه چیست، چه مسئله‌ای را حل می‌کند، ساختار داده چگونه است، جریان کاربر چگونه است و Agent چه قوانینی را باید رعایت کند.

---

# 1. Project Overview

این پروژه یک فروشگاه آنلاین برای **طراحی و سفارش کارت فلزی سفارشی** است.

این پروژه یک فروشگاه معمولی نیست که کاربر از بین محصولات آماده، یک محصول را انتخاب و خریداری کند.

در این سیستم، محصول نهایی توسط خود کاربر ساخته می‌شود.

کاربر ابتدا نوع کارت را انتخاب می‌کند، سپس رنگ کارت، گروه طرح، طرح و رنگ طرح را انتخاب می‌کند. پس از انتخاب طرح، سیستم باید یک Preview زنده از کارت نمایش دهد.

در مرحله بعد، کاربر اطلاعات موردنیاز پشت کارت را وارد می‌کند و می‌تواند موقعیت نمایش این اطلاعات را تغییر دهد. Preview پشت کارت نیز باید به صورت زنده تغییر کند.

پس از تأیید نهایی، طراحی ساخته‌شده به سبد خرید اضافه شده و کاربر فرآیند سفارش و پرداخت را انجام می‌دهد.

---

# 2. Core Concept

مفهوم اصلی سیستم:

```text
Predefined Design Data
        +
Card Type
        +
Card Color
        +
Design
        +
Design Color
        +
User Custom Data
        ↓
Customized Card
        ↓
Cart
        ↓
Order
        ↓
Payment
```

طرح‌ها توسط مدیر سایت از طریق پنل مدیریت وارد سیستم می‌شوند.

کاربر نمی‌تواند طرح جدید ایجاد کند.

کاربر فقط می‌تواند از داده‌هایی که مدیر در سیستم تعریف کرده، کارت خود را شخصی‌سازی کند.

---

# 3. Technology Stack

Technology Stack فعلی پروژه:

* Laravel 13.8
* Livewire 4.3
* Filament 4
* PHP 8.3+
* MySQL
* PhpStorm

## نقش تکنولوژی‌ها

### Laravel

Laravel هسته اصلی Backend است و مسئول موارد زیر است:

* Application Architecture
* Routing
* Authentication
* Models
* Database
* Business Logic
* Validation
* Orders
* Payments
* Persistence

### Livewire

Livewire برای بخش‌های Interactive Frontend استفاده می‌شود.

مهم‌ترین کاربردها:

* انتخاب نوع کارت
* انتخاب رنگ کارت
* انتخاب گروه طرح
* انتخاب طرح
* انتخاب رنگ طرح
* اعمال محدودیت‌های طراحی
* Live Preview
* ورود اطلاعات پشت کارت
* Live Preview پشت کارت
* تغییر موقعیت اطلاعات
* مدیریت State فرآیند طراحی

### Filament

Filament پنل مدیریت پروژه است.

مدیر سایت از طریق Filament داده‌های مرجع و مدیریتی را کنترل می‌کند.

### MySQL

Database اصلی پروژه است.

---

# 4. Important Architectural Principle

این پروژه دو بخش اصلی دارد:

```text
                    Laravel
                       |
          ┌────────────┴────────────┐
          |                         |
       Filament                  Livewire
       Admin                     Frontend
          |                         |
          |                         |
   Data Management          Card Customization
          |                         |
          └────────────┬────────────┘
                       |
                     MySQL
```

## Filament

مسئول مدیریت داده‌ها است.

## Livewire

مسئول تجربه تعاملی کاربر و طراحی کارت است.

## Laravel

مسئول منطق اصلی و Business Logic سیستم است.

این مسئولیت‌ها نباید بدون دلیل با یکدیگر ترکیب شوند.

---

# 5. Product Philosophy

در این پروژه Product به معنی یک محصول کاملاً آماده نیست.

یک محصول نهایی از ترکیب چند داده ساخته می‌شود:

```text
Card Type
+
Card Color
+
Design
+
Design Color
+
Customization Data
```

بنابراین نباید معماری سیستم را مانند یک فروشگاه ساده WooCommerce یا Product CRUD معمولی در نظر گرفت.

طرح‌ها و رنگ‌ها داده‌های مرجع هستند.

Customization نتیجه انتخاب‌های کاربر است.

Order نتیجه خرید Customization است.

---

# 6. Database Structure

Database فعلی دقیقاً شامل ساختار زیر است.

> **این ساختار مرجع است.**
>
> Agent نباید بدون درخواست یا تأیید صریح، جدول، ستون یا ساختار جدیدی به آن اضافه یا از آن حذف کند.

---

# 7. Users

## Table: users

```text
id
name
phone
address
password
```

### Purpose

اطلاعات کاربران سایت.

### Important Rules

* `phone` شناسه Unique کاربر است.
* Email در ساختار فعلی سیستم وجود ندارد.
* Agent نباید بدون درخواست، email یا فیلدهای احراز هویت اضافی ایجاد کند.

---

# 8. Colors

## Table: colors

```text
id
name
color_code
```

### Purpose

جدول مرجع تمام رنگ‌های مورد استفاده در سیستم.

`color_code` مقدار HEX رنگ را نگهداری می‌کند.

مثال:

```text
name: Black
color_code: #000000
```

این جدول می‌تواند توسط بخش‌های مختلف سیستم مورد استفاده قرار بگیرد.

---

# 9. Design Categories

## Table: cate_designs

```text
id
name
is_active
```

### Purpose

دسته‌بندی اصلی طرح‌ها.

مثال:

```text
رمزارزها
مینیمال
هنری
ورزشی
```

`is_active` مشخص می‌کند دسته‌بندی در بخش کاربری قابل نمایش باشد یا خیر.

---

# 10. Design Groups

## Table: group_designs

```text
id
cate_design_id
name
```

### Purpose

هر Category شامل چند Group است.

ساختار:

```text
Category
   ↓
Group
```

مثال:

```text
Category: رمزارزها

Groups:
- Bitcoin
- Ethereum
- Solana
```

---

# 11. Designs

## Table: designs

```text
id
group_design_id
name
image_path
```

### Purpose

هر Group شامل چند Design است.

ساختار:

```text
Category
    ↓
Group
    ↓
Design
```

مثال:

```text
Group: رمزارزها

Designs:
- Bitcoin
- Ethereum
- Solana
```

---

# 12. Design Images

## Table: design_images

```text
id
design_id
color_id
image_path
```

### Purpose

یک Design می‌تواند برای رنگ‌های مختلف تصویر متفاوت داشته باشد.

مثال:

```text
Bitcoin
 ├── Gold
 │     └── image_path
 │
 ├── Silver
 │     └── image_path
 │
 └── White
       └── image_path
```

بنابراین `design_images` مشخص می‌کند:

> این Design در این Color از چه تصویر/Assetای استفاده می‌کند؟

این جدول نقش مهمی در سیستم Live Preview دارد.

---

# 13. Design Color Restrictions

## Table: design_color_restrictions

```text
id
design_image_id
forbidden_card_color_id
```

### Purpose

این جدول محدودیت سازگاری بین رنگ یک Design Image و رنگ کارت را مشخص می‌کند.

مثال:

```text
Design Image:
Bitcoin Gold

Forbidden Card Color:
Matte Black
```

معنی:

```text
Bitcoin Gold
+
Matte Black Card
=
Not Allowed
```

در این حالت رنگ Gold برای آن طرح نباید به کاربری که کارت Matte Black انتخاب کرده، نمایش داده شود.

---

# 14. Critical Business Rule: Design Color Compatibility

این یکی از مهم‌ترین قوانین Business Logic پروژه است.

کاربر ابتدا رنگ کارت را انتخاب می‌کند.

مثلاً:

```text
Card Color = Matte Black
```

سپس یک Design را انتخاب می‌کند.

مثلاً:

```text
Design = Bitcoin
```

حالا سیستم باید رنگ‌های قابل استفاده این Design را مشخص کند.

فرض کنیم:

```text
Bitcoin Gold → Forbidden on Matte Black
Bitcoin Silver → Allowed
Bitcoin White → Allowed
```

در UI کاربر باید فقط این‌ها را ببیند:

```text
Silver
White
```

و نباید Gold را ببیند.

---

# 15. Security Rule for Restrictions

مخفی کردن گزینه در Frontend کافی نیست.

حتی اگر Frontend رنگ غیرمجاز را نمایش ندهد، Backend باید هنگام ثبت یا تأیید Customization دوباره سازگاری را بررسی کند.

بنابراین:

```text
Frontend filtering
+
Backend validation
```

هر دو لازم هستند.

Agent نباید فقط به UI filtering اکتفا کند.

---

# 16. Card Types

## Table: card_types

```text
id
color_id
base_price
is_available
```

### Purpose

نوع/نسخه قابل سفارش کارت همراه با رنگ، قیمت پایه و وضعیت availability.

`base_price` قیمت پایه کارت است.

`is_available` مشخص می‌کند این گزینه در حال حاضر قابل سفارش است یا خیر.

---

# 17. Customizations

## Table: customizations

```text
id
card_type_id
design_image_id
customizable_id
customizable_type
```

### Purpose

این جدول نشان‌دهنده یک Customization ساخته‌شده توسط کاربر است.

به صورت مفهومی:

```text
Card Type
+
Design Image
+
Customizable Data
=
Customization
```

---

# 18. Polymorphic Customization

فیلدهای:

```text
customizable_id
customizable_type
```

برای اتصال Customization به نوع داده اختصاصی آن استفاده می‌شوند.

در حال حاضر دو نوع داده وجود دارد:

```text
bank_card_data
fuel_card_data
```

بنابراین:

```text
Customization
       |
       └── Polymorphic Relation
               |
               ├── BankCardData
               |
               └── FuelCardData
```

Agent نباید برای این دو نوع، ساختار جداگانه و غیرمرتبط با Customization ایجاد کند مگر اینکه معماری فعلی صراحتاً تغییر داده شود.

---

# 19. Bank Card Data

## Table: bank_card_data

```text
id
holder_name
card_number
cvv2
expiry_date
```

### Purpose

اطلاعات اختصاصی کارت بانکی.

این داده‌ها توسط کاربر هنگام طراحی کارت وارد می‌شوند.

---

# 20. Fuel Card Data

## Table: fuel_card_data

```text
id
owner_name
car_model
vin_number
sys_number
chip_type
```

### Purpose

اطلاعات اختصاصی کارت سوخت.

این داده‌ها هنگام طراحی کارت سوخت توسط کاربر وارد می‌شوند.

---

# 21. Orders

## Table: orders

```text
id
user_id
total_price
status
```

### Purpose

نماینده سفارش کاربر.

هر Order متعلق به یک User است.

---

# 22. Order Items

## Table: order_items

```text
id
order_id
card_type_id
customization_id
quantity
price
```

### Purpose

هر Order Item نشان‌دهنده یک Customization خریداری‌شده در یک Order است.

ساختار:

```text
Order
  |
  ├── Order Item
  │      ├── Card Type
  │      └── Customization
  │
  └── Order Item
         ├── Card Type
         └── Customization
```

---

# 23. Database Relationship Map

ساختار مفهومی روابط:

```text
users
  |
  └── orders
        |
        └── order_items
              |
              ├── card_types
              |
              └── customizations
                     |
                     ├── card_types
                     |
                     ├── design_images
                     |
                     └── polymorphic customizable
                              |
                              ├── bank_card_data
                              └── fuel_card_data


cate_designs
  |
  └── group_designs
          |
          └── designs
                 |
                 └── design_images
                        |
                        ├── colors
                        |
                        └── design_color_restrictions
                                   |
                                   └── colors


card_types
    |
    └── colors
```

---

# 24. User Journey

جریان اصلی کاربر به این صورت است:

```text
Home
 ↓
Design
 ↓
Select Card Type
 ↓
Select Card Color
 ↓
Select Design Group
 ↓
Select Design
 ↓
Select Design Color
 ↓
Live Front Preview
 ↓
Confirm Front
 ↓
Enter Back Card Data
 ↓
Live Back Preview
 ↓
Move / Position Data
 ↓
Confirm Customization
 ↓
Cart
 ↓
Order
 ↓
Payment
```

---

# 25. Design Flow

صفحه Design هسته اصلی Frontend پروژه است.

## Step 1 — Card Type

کاربر انتخاب می‌کند:

```text
Bank Metal Card
Fuel Card
```

---

## Step 2 — Card Color

کاربر رنگ کارت را انتخاب می‌کند.

رنگ‌های موجود باید بر اساس وضعیت واقعی `card_types` و ارتباط آن با `colors` مدیریت شوند.

---

## Step 3 — Design Group

گروه‌های طرح مناسب نمایش داده می‌شوند.

---

## Step 4 — Design

طرح‌های موجود در Group نمایش داده می‌شوند.

---

## Step 5 — Design Color

رنگ‌های موجود برای Design نمایش داده می‌شوند.

در این مرحله `design_color_restrictions` باید بررسی شود.

---

## Step 6 — Live Preview

پس از انتخاب Design Image، تصویر مناسب باید روی کارت نمایش داده شود.

تغییر انتخاب‌های کاربر باید باعث تغییر Preview شود.

---

# 26. Front Card Preview

Front Preview باید ترکیب انتخاب‌های زیر را نمایش دهد:

```text
Selected Card Type
+
Selected Card Color
+
Selected Design Image
```

هدف Preview این است که کاربر قبل از تأیید، نتیجه تقریبی کارت را مشاهده کند.

---

# 27. Back Card Customization

بعد از تأیید Front، کاربر وارد بخش پشت کارت می‌شود.

برای Bank Card:

```text
holder_name
card_number
cvv2
expiry_date
```

برای Fuel Card:

```text
owner_name
car_model
vin_number
sys_number
chip_type
```

---

# 28. Back Card Live Preview

هنگام ورود اطلاعات، Preview باید به صورت Live تغییر کند.

Concept:

```text
User Input
    ↓
Livewire State
    ↓
Back Card Preview
```

هر تغییر Input باید در Preview منعکس شود.

---

# 29. Position Customization

کاربر باید بتواند موقعیت نمایش اطلاعات پشت کارت را تغییر دهد.

این قابلیت بخشی از فرآیند Customization است.

مفهوم:

```text
Field
+
Value
+
Position
```

Agent باید توجه کند که Position Management بخشی از منطق Customization است و نباید صرفاً به یک UI ظاهری تبدیل شود که هنگام ذخیره شدن اطلاعات آن از بین برود.

---

# 30. Customization Confirmation

پس از تکمیل Front و Back:

```text
Front Design
+
Back Data
+
Positions
```

کاربر Customization را تأیید می‌کند.

سپس Customization باید به عنوان یک محصول سفارشی قابل سفارش وارد مرحله Cart شود.

---

# 31. Cart and Order

پس از تأیید Customization:

```text
Customization
      ↓
Cart
      ↓
Order
      ↓
Payment
```

Order از Order Items تشکیل می‌شود.

هر Order Item به:

```text
card_type_id
customization_id
quantity
price
```

متصل است.

---

# 32. Pricing Concept

قیمت پایه در:

```text
card_types.base_price
```

قرار دارد.

قیمت خرید در:

```text
order_items.price
```

ثبت می‌شود.

`orders.total_price` مجموع قیمت سفارش است.

Agent نباید بدون نیاز، سیستم قیمت‌گذاری جدید یا ساختار قیمت‌گذاری دیگری ایجاد کند.

---

# 33. Admin Panel

Admin Panel با Filament 4 ساخته می‌شود.

هدف پنل:

> مدیریت داده‌های مرجع و مدیریتی سیستم.

مدیر باید بتواند موارد مربوط به سیستم طراحی را مدیریت کند:

```text
Colors
Categories
Groups
Designs
Design Images
Design Restrictions
Card Types
```

و همچنین اطلاعات مدیریتی موردنیاز مانند:

```text
Users
Orders
```

را مشاهده/مدیریت کند.

---

# 34. Admin Data Dependency

داده‌های پنل دارای Dependency هستند.

ساختار:

```text
Category
   ↓
Group
   ↓
Design
   ↓
Design Image
   ↓
Color
   ↓
Restriction
```

بنابراین هنگام طراحی Admin Panel باید این وابستگی‌ها در نظر گرفته شوند.

مثلاً ایجاد Design بدون Group منطقی نیست.

---

# 35. Frontend Data Dependency

Frontend نیز باید بر اساس Dependencyها کار کند.

کاربر نباید بتواند به مرحله‌ای برسد که داده‌های موردنیاز مرحله قبل وجود نداشته باشد.

مثلاً:

```text
No Card Color
      ↓
Cannot reliably determine valid Design Colors
```

بنابراین State انتخاب‌های کاربر باید در طول فرآیند طراحی معتبر باقی بماند.

---

# 36. State Management

فرآیند طراحی دارای State است.

State مفهومی:

```text
selectedCardType
selectedCardColor
selectedDesignGroup
selectedDesign
selectedDesignColor
customizationType
customData
positions
```

این State باید به شکل کنترل‌شده مدیریت شود.

اگر انتخاب قبلی تغییر کرد، Stateهای وابسته باید بررسی شوند.

مثلاً اگر کاربر:

```text
Card Color A
```

را انتخاب کرده و سپس به:

```text
Card Color B
```

تغییر دهد، ممکن است Design Color انتخاب‌شده قبلی دیگر مجاز نباشد.

سیستم باید چنین Stateهای نامعتبر را مدیریت کند.

---

# 37. Backend Validation

تمام انتخاب‌های حساس باید در Backend نیز بررسی شوند.

مثال:

کاربر نمی‌تواند صرفاً با ارسال Request دستی، یک Design Color ممنوع را برای Card Color خاص ثبت کند.

Backend باید بررسی کند:

```text
Card Type valid?
Card Color valid?
Design Image valid?
Design Color compatible?
Customization type valid?
```

Frontend صرفاً لایه تجربه کاربری است.

Backend منبع نهایی اعتبارسنجی است.

---

# 38. Data Integrity

Agent باید همیشه Data Integrity را حفظ کند.

یعنی:

* Foreign Keyها معتبر باشند.
* Relationها مطابق Database باشند.
* رکوردهای وابسته بدون بررسی حذف نشوند.
* Customization به داده نامعتبر متصل نشود.
* Order Item به Customization نامعتبر اشاره نکند.
* Design Image متعلق به Design صحیح باشد.
* Design Image از Color معتبر استفاده کند.
* Restriction به Design Image و Color معتبر اشاره کند.

---

# 39. Important Design Principle

`design_images` فقط یک جدول ساده برای ذخیره عکس نیست.

این جدول در واقع مشخص می‌کند:

```text
Design
+
Design Color
+
Image Asset
```

چه رابطه‌ای با هم دارند.

بنابراین هنگام پیاده‌سازی Design Selection و Preview باید این ساختار در نظر گرفته شود.

---

# 40. Important Restriction Principle

`design_color_restrictions` نیز صرفاً یک جدول CRUD ساده نیست.

این جدول بخشی از Business Rule اصلی سیستم است.

Rule:

```text
If DesignImage is forbidden for selected CardColor:
    DesignImage must not be selectable.
```

و:

```text
If user bypasses frontend:
    Backend must reject invalid combination.
```

---

# 41. AI Agent Rules

این بخش برای تمام AI Agentهایی که روی پروژه کار می‌کنند الزامی است.

## Rule 1 — Read Before Modify

قبل از تغییر هر فایل:

1. ساختار پروژه را بررسی کن.
2. فایل‌های مرتبط را بخوان.
3. Modelها و Migrationهای مرتبط را بررسی کن.
4. Context این فایل را در نظر بگیر.
5. سپس تغییر بده.

---

## Rule 2 — Do Not Guess

اگر چیزی در پروژه مشخص نیست:

**حدس نزن.**

مثلاً اگر نمی‌دانی:

* ساختار تصویر چیست
* رفتار خاصی چگونه باید باشد
* قیمت چگونه محاسبه می‌شود
* Position چگونه باید ذخیره شود

باید ابتدا مسئله را مشخص کنی.

---

## Rule 3 — Database Is Source of Truth

ساختار Database فعلی مرجع اصلی Data Model است.

Agent نباید صرفاً به دلیل اینکه یک معماری دیگر «بهتر» به نظر می‌رسد، Database را تغییر دهد.

---

## Rule 4 — No Unrequested Schema Changes

بدون تأیید صریح کاربر:

* Table جدید نساز.
* Column جدید اضافه نکن.
* Column حذف نکن.
* Relationship اصلی را تغییر نده.
* ساختار Polymorphic را تغییر نده.

اگر تغییر Schema واقعاً لازم است، ابتدا دلیل آن را توضیح بده و منتظر تأیید بمان.

---

## Rule 5 — Do Not Rewrite Existing Work

اگر یک بخش قبلاً پیاده‌سازی شده و کار می‌کند، آن را صرفاً به دلیل ترجیح شخصی بازنویسی نکن.

قبل از Refactor:

```text
Why?
What problem does it solve?
What files are affected?
What behavior can change?
```

را مشخص کن.

---

## Rule 6 — Keep Business Logic Out of Views

Business Logic نباید مستقیماً داخل Blade View قرار گیرد.

منطق مهم سیستم باید در Backend و لایه مناسب قرار داشته باشد.

---

## Rule 7 — Backend Is Authoritative

هیچ Validation مهمی نباید فقط در JavaScript یا Livewire UI انجام شود.

هر چیزی که روی امنیت، سفارش، قیمت یا اعتبار Customization تأثیر دارد باید در Backend نیز بررسی شود.

---

## Rule 8 — Do Not Create Duplicate Logic

اگر یک Business Rule قبلاً در یک محل مشخص پیاده‌سازی شده، همان منطق را در چند Component مختلف تکرار نکن.

قبل از ایجاد Logic جدید، بررسی کن آیا Logic مشابه در پروژه وجود دارد یا خیر.

---

## Rule 9 — Preserve Existing Naming

از Naming موجود پروژه پیروی کن.

نام‌های فعلی:

```text
cate_designs
group_designs
designs
design_images
design_color_restrictions
card_types
customizations
bank_card_data
fuel_card_data
orders
order_items
```

بدون دلیل آن‌ها را تغییر نده.

---

## Rule 10 — Small Changes

تغییرات را مرحله‌ای انجام بده.

به جای تغییر همزمان ده‌ها فایل:

```text
Analyze
→
Implement one logical feature
→
Test
→
Review
→
Continue
```

---

# 42. AI Implementation Workflow

هر Task باید با این فرآیند انجام شود:

```text
1. Understand
2. Inspect existing code
3. Identify affected files
4. Explain intended change
5. Implement
6. Run relevant tests/checks
7. Verify behavior
8. Report what changed
```

Agent نباید مستقیماً بدون بررسی پروژه شروع به تولید فایل کند.

---

# 43. Existing Project Status

در حال حاضر:

```text
Laravel Project
        ↓
Database Connected
        ↓
Migrations Created
        ↓
Migrations Executed
        ↓
Index Review Completed
        ↓
CURRENT STATE
```

قدم منطقی بعدی:

```text
Models
```

سپس:

```text
Relationships
```

سپس:

```text
Model / Relationship Testing
```

بعد از آن:

```text
Filament Admin Panel
```

و پس از آماده شدن داده‌های مدیریتی:

```text
Livewire Card Designer
```

---

# 44. Development Order

ترتیب کلی توسعه:

```text
Database
    ↓
Models
    ↓
Relationships
    ↓
Database Layer Verification
    ↓
Filament Admin
    ↓
Seed/Test Data
    ↓
Card Designer
    ↓
Front Preview
    ↓
Back Card Customization
    ↓
Customization Persistence
    ↓
Cart
    ↓
Orders
    ↓
Payment
    ↓
Testing
```

این ترتیب باید تا زمانی که دلیل فنی مشخصی برای تغییر آن وجود ندارد حفظ شود.

---

# 45. What The Agent Must Understand

Agent باید این پروژه را این‌گونه درک کند:

این یک:

```text
Normal E-Commerce
```

نیست.

بلکه:

```text
Custom Product Configuration System
+
E-Commerce
```

است.

سه هسته اصلی پروژه:

```text
1. Design Management
2. Customization Engine
3. Order & Payment
```

و مهم‌ترین Business Rule:

```text
Card Color
      ×
Design Color
      ↓
Compatibility
```

است.

---

# 46. Final Project Mental Model

مدیر:

```text
Creates and manages available designs
```

کاربر:

```text
Selects available configuration
```

سیستم:

```text
Validates configuration
```

کاربر:

```text
Customizes card
```

سیستم:

```text
Creates Customization
```

کاربر:

```text
Adds it to cart
```

سیستم:

```text
Creates Order
```

کاربر:

```text
Pays
```

---

# 47. Absolute Constraints

موارد زیر تا زمانی که کاربر صراحتاً درخواست نکرده است، نباید تغییر کنند:

```text
Database Structure
Table Names
Existing Column Names
Core Relationships
Polymorphic Customization Concept
Design Restriction Concept
Card Design Flow
Admin/Frontend Separation
```

هر تغییر در این موارد باید قبل از Implementation مطرح و تأیید شود.

---

# 48. Current Objective

هدف فعلی پروژه تکمیل لایه Database نیست؛ Database و Migrationها در حال حاضر آماده هستند.

هدف فعلی:

```text
Database
   ↓
Models
   ↓
Relationships
   ↓
Verification
```

پس از تکمیل این بخش، پروژه وارد Admin Panel خواهد شد.

---

# 49. Golden Rule

> **Do not optimize what you have not understood.**
>
> ابتدا پروژه و ساختار موجود را بفهم، سپس تغییر بده.

> **Do not invent requirements.**
>
> چیزی را که کاربر مشخص نکرده، از خودت به عنوان Requirement فرض نکن.

> **Do not change architecture silently.**
>
> تغییر معماری باید توضیح داده شود و تأیید شود.

> **Existing code is part of the specification.**
>
> کدی که قبلاً نوشته شده بخشی از Context پروژه است و نباید بدون بررسی بازنویسی شود.

---

# End of Project Context
