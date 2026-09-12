# DATABASE_DESIGN.md

# طراحی پایگاه داده نهایی برای سیستم تجارت الکترونیک کارت مالی

---

## 1. فهرست جدول‌ها

### جدول‌های الزامی (REQUIRED)

1. `users` - کاربران
2. `products` - محصولات (کارت بانکی، کارت سوخت، محصولات استاندارد)
3. `colors` - رنگ‌ها (رنگ کارت و رنگ طرح)
4. `cate_designs` - دسته‌بندی طرح‌ها
5. `designs` - طرح‌ها
6. `design_images` - تصاویر طرح (رنگ variant)
7. `product_color_prices` - قیمت رنگ‌های محصول
8. `orders` - سفارش‌ها
9. `order_items` - آیتم‌های سفارش (شامل snapshot customization)

### جدول‌های اختیاری (OPTIONAL)

10. `homepage_sections` - محتوای داینامیک صفحه اصلی (CMS)

### جدول‌های آینده (FUTURE)

11. `roles` - نقش‌های ادمین (برای فازهای بعدی احتمالی)
12. `audit_log` - لاگ امنیتی (برای ممیزی و ردیابی تغییرات)

### جدول‌های حذف شده (REMOVED)

- ~~`card_types`~~ → ادغام در جدول `products`
- ~~`ank_card_data`~~ → ذخیره‌سازی در `order_items.customization_json`
- ~~`uel_card_data`~~ → ذخیره‌سازی در `order_items.customization_json`
- ~~`ustomizations`~~ → ادغام در `order_items.customization_json`
- ~~`compatibility_restrictions`~~ → جدا نگه داشته شده برای مدیریت محدودیت طرح‌های رنگی
- ~~`art`~~ → راه‌اندازی inevitable برای ۲۴ ساعته

---

## 2. جدول PRODUCTS

### هدف

یکی از مهم‌ترین جداول سیستم است. تمام محصولات (کارت بانکی، کارت سوخت، محصولات استاندارد) در یک جدول نگهداری می‌شوند.

### فیلدها

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| type | VARCHAR(20) | NO | NULL | INDEX | NO | نوع محصول: 'bank', 'fuel', 'standard' |
| name | VARCHAR(255) | NO | NULL | INDEX | NO | نام نمایشی محصول |
| slug | VARCHAR(255) | YES | NULL | UNIQUE | YES | URL-friendly slug |
| description | TEXT | YES | NULL | NO | NO | توضیحات محصول |
| base_price | INTEGER | YES | NULL | INDEX | NO | قیمت پایه (فقط fallback برای محصولات standard) |
| is_active | BOOLEAN | NO | 1 | INDEX | NO | موجود بودن/عدم موجود بودن |
| supports_chip_selection | BOOLEAN | NO | 0 | INDEX | NO | آیا محصول امکان انتخاب سایز تراشه دارد؟ (فقط fuel) |
| engraving_config | JSON | YES | NULL | INDEX | NO | تنظیمات حکاکی (فقط standard) |
| meta_tag_title | VARCHAR(255) | YES | NULL | INDEX | NO | عنوان SEO |
| meta_tag_desc | VARCHAR(500) | YES | NULL | INDEX | NO | توضیحات SEO |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش آخر |

### راه‌اندازی

```sql
CREATE TABLE products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type VARCHAR(20) NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NULL UNIQUE,
  description TEXT NULL,
  base_price INTEGER NULL,
  is_active BOOLEAN NOT NULL DEFAULT 1,
  supports_chip_selection BOOLEAN NOT NULL DEFAULT 0,
  engraving_config JSON NULL,
  meta_tag_title VARCHAR(255) NULL,
  meta_tag_desc VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_products_type (type),
  INDEX idx_products_active (is_active)
);
```

### قیمت‌گذاری رنگ کارت (Product Color Pricing)

برای محصولات نوع `bank`، قیمت نهایی بر اساس رنگ انتخابی کارت تعیین می‌شود.

هر محصول بانکی می‌تواند:
- چند رنگ مختلف داشته باشد
- برای هر رنگ قیمت متفاوت داشته باشد
- بعضی رنگ‌ها را غیرفعال کند

### جدول PRODUCT_COLOR_PRICES

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| product_id | INTEGER | NO | NULL | FK | NO | محصول بانکی |
| color_id | INTEGER | NO | NULL | FK | NO | رنگ کارت |
| price | INTEGER | NO | 0 | INDEX | NO | قیمت این رنگ برای محصول |
| is_active | BOOLEAN | NO | 1 | INDEX | NO | فعال/غیرفعال بودن رنگ برای این محصول |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

```sql
CREATE TABLE product_color_prices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  color_id INTEGER NOT NULL,
  price INTEGER NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_product_color_prices_product
    FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_product_color_prices_color
    FOREIGN KEY (color_id)
    REFERENCES colors(id)
    ON DELETE RESTRICT,
  UNIQUE (product_id, color_id),
  INDEX idx_product_color_prices_product (product_id),
  INDEX idx_product_color_prices_color (color_id),
  INDEX idx_product_color_prices_active (is_active)
);
```

---

## 3. COLORS

### هدف

مدیریت رنگ‌های استفاده‌شده در کارت‌های بانکی و طرح‌های محصول. هر دو نوع رنگ در یک جدول مرکزی نگهداری می‌شوند.

### فیلدها

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| name | VARCHAR(100) | NO | NULL | INDEX | NO | نام نمایشی رنگ (مثلاً: صورتی نعنا، مشکی مات) |
| code_hex | VARCHAR(7) | YES | NULL | UNIQUE | YES | کد Hex رنگ (#RRGGBB) |
| is_active | BOOLEAN | NO | 1 | INDEX | NO | فعال بودن رنگ |
| sort_order | INTEGER | NO | 0 | INDEX | NO | ترتیب نمایش در صفحات ادمین |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش آخر |

### راه‌اندازی

```sql
CREATE TABLE colors (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(100) NOT NULL,
  code_hex VARCHAR(7) NULL UNIQUE,
  is_active BOOLEAN NOT NULL DEFAULT 1,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_colors_name (name),
  INDEX idx_colors_active (is_active),
  INDEX idx_colors_order (sort_order)
);
```

---

## 4. DESIGN SYSTEM

### هدف

مدیریت سلسله‌مراتبی طرح‌ها:
- دسته‌بندی اصلی (Category)
- طرح (Design)
- تصویر رنگ/Variant (Design Image)

معماری نهایی:

```text
cate_designs
    └── designs
            └── design_images
```

### تصمیم معماری

جدول `group_designs` حذف شد.

دلیل:
- نیاز کسب‌وکار فقط category → design → variant است
- اضافه کردن Group لایه اضافی و غیرضروری ایجاد می‌کند
- ادمین غیرتکنیکال راحت‌تر با category مستقیم کار می‌کند
- UI ادمین ساده‌تر می‌شود
- Queryهای frontend ساده‌تر می‌شوند
- طراحی فعلی پروژه نیازی به سطح میانی ندارد

### 4.1 جدول CATE_DESIGNS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| name | VARCHAR(100) | NO | NULL | INDEX | NO | نام دسته‌بندی (مثلاً: حیوانات، گل‌ها) |
| is_active | BOOLEAN | NO | 1 | INDEX | NO | فعال بودن دسته |
| sort_order | INTEGER | NO | 0 | INDEX | NO | ترتیب نمایش |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

```sql
CREATE TABLE cate_designs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(100) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT 1,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_cate_designs_active (is_active),
  INDEX idx_cate_designs_order (sort_order)
);
```

### 4.2 جدول DESIGNS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| cate_design_id | INTEGER | NO | NULL | FK | NO | دسته‌بندی مرکزی |
| name | VARCHAR(100) | NO | NULL | INDEX | NO | نام طرح (مثلاً: اژدها، عقاب، Carbon) |
| is_visible | BOOLEAN | NO | 1 | INDEX | NO | قابل مشاهده در وب |
| sort_order | INTEGER | NO | 0 | INDEX | NO | ترتیب نمایش |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

```sql
CREATE TABLE designs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cate_design_id INTEGER NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_visible BOOLEAN NOT NULL DEFAULT 1,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_designs_category
    FOREIGN KEY (cate_design_id)
    REFERENCES cate_designs(id)
    ON DELETE CASCADE,
  INDEX idx_designs_category (cate_design_id),
  INDEX idx_designs_visible (is_visible),
  INDEX idx_designs_order (sort_order)
);
```

### 4.3 جدول DESIGN_IMAGES (Design Color Variants)

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| design_id | INTEGER | NO | NULL | FK | NO | طرح مرکزی |
| color_id | INTEGER | NO | NULL | FK | NO | رنگ Variant |
| image_path | VARCHAR(255) | NO | NULL | INDEX | NO | تصویر Variant |
| is_visible | BOOLEAN | NO | 1 | INDEX | NO | قابل مشاهده |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

**نکته مهم:**
- هر رکورد در `design_images` یک variant رنگی مستقل از طرح است
- تصویر preview نهایی فقط در `design_images.image_path` نگهداری می‌شود
- جدول `designs` مسئول نگهداری metadata طرح است، نه تصویر preview
- frontend در designer فقط با `design_images` کار می‌کند
- admin برای هر variant رنگی تصویر مستقل آپلود می‌کند
- این ساختار از duplicate image responsibility جلوگیری می‌کند

```sql
CREATE TABLE design_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  design_id INTEGER NOT NULL,
  color_id INTEGER NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_visible BOOLEAN NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_design_images_design
    FOREIGN KEY (design_id)
    REFERENCES designs(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_design_images_color
    FOREIGN KEY (color_id)
    REFERENCES colors(id)
    ON DELETE CASCADE,
  INDEX idx_design_images_design (design_id),
  INDEX idx_design_images_color (color_id)
);
```

---

## 5. DESIGN ↔ CARD COLOR COMPATIBILITY

### هدف

فقط برای کارت‌های بانکی. محدودیت‌های رنگی بین رنگ طرح و رنگ کارت تعریف می‌شود.

مثال:
- رنگ طرح: سفید
- رنگ کارت: مشکی مات
→ ناسازگاری (عدم امکان استفاده از طرح سفید روی کارت مشکی)

### جدول COMPATIBILITY_RESTRICTIONS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| design_image_id | INTEGER | NO | NULL | FK | NO | طرح رنگی متضاد |
| forbidden_card_color_id | INTEGER | NO | NULL | FK | NO | رنگ کارت نامحتمل |

```sql
CREATE TABLE compatibility_restrictions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  design_image_id INTEGER NOT NULL,
  forbidden_card_color_id INTEGER NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_compatibility_design_image
    FOREIGN KEY (design_image_id)
    REFERENCES design_images(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_compatibility_forbidden_color
    FOREIGN KEY (forbidden_card_color_id)
    REFERENCES colors(id)
    ON DELETE CASCADE,
  UNIQUE (design_image_id, forbidden_card_color_id),
  INDEX idx_compatibility_design_image (design_image_id),
  INDEX idx_compatibility_forbidden_color (forbidden_card_color_id)
);
```

---

## 6. BANK CARD CUSTOMIZATION

### هدف

انتخاب نهایی کارت توسط مشتری نگهداری می‌شود، نه تخلیه شدن به جدول جداگانه.

### ساختار `customization_json` در جدول `ORDER_ITEMS`

```json
{
  "selected_product_id": 5,
  "product_type": "bank",
  "product_name": "کارت بانکی فلزی",
  "selected_card_color_id": 3,
  "selected_card_color_name": "مشکی مات",
  "selected_color_price": 1500000,
  "final_calculated_price": 1500000,
  "selected_design_id": 12,
  "design_name": "Dragons High",
  "selected_design_image_id": 45,
  "design_image_name": "White Dragons High - White",
  "text_elements": {
    "card_number": {
      "top": "15",
      "left": "5",
      "width": "90",
      "fontSize": "16"
    },
    "holder_name": {
      "top": "45",
      "left": "5",
      "width": "60",
      "fontSize": "13"
    },
    "expiry_date": {
      "top": "45",
      "left": "70",
      "width": "25",
      "fontSize": "13"
    },
    "cvv2": {
      "top": "65",
      "left": "35",
      "width": "30",
      "fontSize": "12"
    }
  },
  "form_state": {
    "holder_name": "علی محمدی",
    "card_number": "XXXX-XXXX-XXXX-5555",
    "cvv2": "***",
    "expiry_date": "12/30"
  },
  "metadata": {
    "customer_ip": "192.168.1.100",
    "created_at": "2026-07-22T14:30:00Z"
  }
}
```

**نکته مهم:**
- فقط نام‌های مرتبط ذخیره می‌شوند، نه کلید‌های منطقی
- فرم State: тільки جزئیات فرم (نام کاربر) ذخیره می‌شود، نه حساسات انتهای تبدیل.
- layout: تبدیل تصویری به تنظیمات

### جدول ORDERS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| user_id | INTEGER | YES | NULL | FK | NO | حساب کاربری |
| total_price | INTEGER | NO | 0 | INDEX | NO | مجموع قیمت |
| status | VARCHAR(20) | NO | 'pending' | INDEX | YES (index) | وضعیت: pending, confirmed, etc. |
| customer_phone | VARCHAR(20) | YES | NULL | INDEX | NO | شماره تماس |
| customer_name | VARCHAR(255) | YES | NULL | INDEX | NO | نام مشتری |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

راه‌اندازی:
```sql
CREATE TABLE orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  total_price INTEGER NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  customer_phone VARCHAR(20) NULL,
  customer_name VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created (created_at)
);
```

### جدول ORDER_ITEMS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| order_id | INTEGER | NO | NULL | FK | NO | سفارش مرکزی |
| product_id | INTEGER | NO | NULL | FK | NO | محصول مرکزی |
| base_price | INTEGER | NO | 0 | INDEX | NO | قیمت واحد (تومان) |
| quantity | INTEGER | NO | 1 | INDEX | NO | تعداد |
| total_price | INTEGER | NO | 0 | INDEX | NO | قیمت کل |
| customization_json | JSON | YES | NULL | INDEX | NO | نسخه-final customization snapshot |

```sql
CREATE TABLE order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  base_price INTEGER NOT NULL DEFAULT 0,
  quantity INTEGER NOT NULL DEFAULT 1,
  total_price INTEGER NOT NULL DEFAULT 0,
  customization_json JSON NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id)
    REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON DELETE RESTRICT,
  INDEX idx_order_items_order (order_id),
  INDEX idx_order_items_product (product_id),
  INDEX idx_order_items_created (created_at)
);
```

---

## 7. TEXT POSITIONING

### توجیه

مختصات متن وقتی نوشته می‌شود، در `customization_json` با مدل ساده و عددی ذخیره می‌شود (نه CSS خام).

### تعریف placement

```json
"text_elements": {
  "field_name": {
    "top": 15,
    "left": 5,
    "width": 90,
    "fontSize": 16,
    "maxFontSize": 24,
    "minFontSize": 8,
    "maxWidth": 300
  }
}
```

### ارزش‌های مختصات

- `top`: موقعیت عمودی در درصد (۰ تا ۱۰۰ درصد)
- `left`: موقعیت افقی در درصد (۰ تا ۱۰۰ درصد)
- `width`: عرض متن در درصد یا پیکسل (به عنوان درصد یا پیکسل)
- `fontSize`: اندازه فونت در واحد (پیکسل)

---

## 8. FUEL CARD

### تصمیم

Fuel card products دارای تراشه ثابت نیستند.

سایز تراشه:
- small
- large

یک انتخاب کاربر در زمان customization است، نه یک ویژگی ثابت محصول.

بنابراین:
- فیلد `chip_type` از جدول `products` حذف شد.
- فقط فیلد `supports_chip_selection` باقی می‌ماند تا مشخص کند آیا محصول امکان انتخاب تراشه دارد یا خیر.
- انتخاب نهایی کاربر داخل `order_items.customization_json` ذخیره می‌شود.

### نمونه snapshot برای fuel card

```json
{
  "selected_product_id": 9,
  "product_type": "fuel",
  "product_name": "کارت سوخت فلزی",
  "selected_chip_size": "large",
  "selected_design_id": 15,
  "selected_design_name": "Sport White",
  "final_calculated_price": 1200000
}
```

### دلیل معماری

این ساختار:
- ساده است
- نیاز به variant system ندارد
- نیازی به جدول جداگانه برای chip sizes ندارد
- انتخاب تراشه را به عنوان customization مشتری نگه می‌دارد
- snapshot نهایی سفارش را کامل حفظ می‌کند

---

## 9. BANK CARD DATA SECURITY

### طبقه‌بندی فیلدها

| فیلد | الزام | حساس | ذخیره در snapshot | رمزگذاری | ماسک (بدون حذف) | ذخیره دائمی | برای تولید فیزیکی مهم |
|------|--------|------|-------------------|----------|----------------|------------|------------------------|
| holder_name | ✅ | ❌ | ✅ کامل | ❌ | ❌ | ✅ | ✅ |
| card_number | ❌ | ✅ | ✅ فقط ماسک | ❌ | ✅ ماسک عملکردی (XXXX-XXXX-XXXX-####) | ✅ | ❌ |
| cvv2 | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ - never stored |
| expiry_date | ❌ | moderate | ✅ فقط سال/ماه | ❌ | ✅ (مثلاً XX/YY) | ✅ | ❌ |
| account_number | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ |
| IBAN | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ |

---

## 10. CART

### تصمیم

سبد خرید بدون استفاده از ` carts` و `cart_items` جدول جداگانه راه‌اندازی می‌شود:
- کاربران لاگین‌شده: صفحات سبد خرید در `cart` جدول مرتبط با `user_id`
- کاربران مهمان: صفحات session-based که بعد از لاگین تبدیل می‌شوند (فومن passing)

با توجه به هدف MVP، از یک جدول ساده استفاده می‌شود، نه سیستم پیچیده.

### جدول CART

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| user_id | INTEGER | YES | NULL | FK | NO | کاربر مرکزی |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

```sql
CREATE TABLE cart (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_cart_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,
  INDEX idx_cart_user (user_id)
);
```

### جدول CART ITEMS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| cart_id | INTEGER | NO | NULL | FK | NO | سبد خرید مرکزی |
| product_id | INTEGER | NO | NULL | FK | NO | محصول مرکزی |
| quantity | INTEGER | NO | 1 | INDEX | NO | تعداد |
| unit_price | INTEGER | NO | 0 | INDEX | NO | قیمت واحد |
| specification_json | JSON | YES | NULL | INDEX | NO | بخش technical product (فقط min JSON) |
| added_at | DATETIME | YES | NULL | INDEX | NO | تاریخ افزودن |
| expires_at | DATETIME | YES | NULL | INDEX | NO | تاریخ اتمام (۲۴ ساعت) |

```sql
CREATE TABLE cart_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cart_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  unit_price INTEGER NOT NULL DEFAULT 0,
  specification_json JSON NULL,
  added_at DATETIME NULL,
  expires_at DATETIME NULL,
  CONSTRAINT fk_cart_items_cart
    FOREIGN KEY (cart_id)
    REFERENCES cart(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product
    FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON DELETE RESTRICT,
  INDEX idx_cart_items_cart (cart_id),
  INDEX idx_cart_items_product (product_id),
  INDEX idx_cart_items_expires (expires_at)
);
```

---

## 11. STANDARD PRODUCTS

### هدف

استفاده از همان سیستم `products` برای محصولات استاندارد (زیورآلات، حکاکی).

### ویژگی‌های اصلی

- `type: 'standard'`
- `engraving_config` JSON: تنظیمات راهنمای حکاکی
- تصاویر متعدد در `engraving_config` یا separate JSON
- قیمت و موجودی (یا تصلیح روی استاتیک نوع تک هدفمند)

---

## 12. USERS

### تصمیم

یک جدول `users` برای هر دو است استفاده از نقش ساده (role) به عنوان تکیه بر rows/permissions در Rush مهزور است.

### جدول USERS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| name | VARCHAR(255) | NO | NULL | INDEX | NO | نام نمایشی |
| phone | VARCHAR(20) | YES | NULL | UNIQUE | YES | شماره موبایل (بخش اصلی هویت) |
| password | VARCHAR(255) | NO | NULL | INDEX | NO | رمز عبور hashed |
| role | VARCHAR(20) | NO | 'customer' | INDEX | NO | نقش: 'customer', 'admin' |
| address | TEXT | YES | NULL | INDEX | NO | آدرس |
| email | VARCHAR(255) | YES | NULL | INDEX | NO | ایمیل (اختیاری) |
| remember_token | VARCHAR(100) | YES | NULL | INDEX | NO | توکن remember me |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

```sql
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'customer',
  address TEXT NULL,
  email VARCHAR(255) NULL,
  remember_token VARCHAR(100) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_users_phone (phone),
  INDEX idx_users_role (role),
  INDEX idx_users_email (email)
);
```

---

## 13. ADMIN / HOMEPAGE

### تصمیم

برای مدیریت محتوای صفحه اصلی از یک جدول ساده استفاده می‌شود، نه صفحه-builder یا CMS پیچیده. محتوای داینامیک با راحتی قابل چرخش است.

### جدول HOMEPAGE_SECTIONS

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| section_key | VARCHAR(50) | NO | NULL | UNIQUE | YES | کلید بخش (مثلاً: hero, features) |
| title | VARCHAR(255) | YES | NULL | INDEX | NO | عنوان بخش |
| content | TEXT | YES | NULL | INDEX | NO | محتوای بخش |
| image_path | VARCHAR(255) | YES | NULL | INDEX | NO | تصویر مرتبط |
| is_active | BOOLEAN | NO | 1 | INDEX | NO | فعال بودن |
| sort_order | INTEGER | NO | 0 | INDEX | NO | ترتیب نمایش |
| display_from | DATE | YES | NULL | INDEX | NO | نمایش از کدام تاریخ |
| display_until | DATE | YES | NULL | INDEX | NO | نمایش تا کدام تاریخ |
| meta_tag_title | VARCHAR(255) | YES | NULL | INDEX | NO | عنوان SEO |
| meta_tag_desc | VARCHAR(500) | YES | NULL | INDEX | NO | توضیحات SEO |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |
| updated_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ویرایش |

```sql
CREATE TABLE homepage_sections (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  section_key VARCHAR(50) NOT NULL UNIQUE,
  title VARCHAR(255) NULL,
  content TEXT NULL,
  image_path VARCHAR(255) NULL,
  is_active BOOLEAN NOT NULL DEFAULT 1,
  sort_order INTEGER NOT NULL DEFAULT 0,
  display_from DATE NULL,
  display_until DATE NULL,
  meta_tag_title VARCHAR(255) NULL,
  meta_tag_desc VARCHAR(500) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_homepage_sections_key (section_key),
  INDEX idx_homepage_sections_active (is_active),
  INDEX idx_homepage_sections_order (sort_order)
);
```

---

## 14. ORDER CUSTOMER INFORMATION

### توجیه

پایگاه داده باید قابلیت نمایش کامل طبقه‌بندی را فراهم کند.

### چه چیزی باید نمایش داده شود؟

- Customer details:
  - name
  - phone
  - address
- Order details:
  - id
  - status
  - total_price
  - created_at
- Product details (از snapshot):
  - product_type
  - product_name
  - unit_price_snapshot
  - quantity
- Customization details:
  - selected_card_color_id
  - selected_card_color_name
  - selected_color_price
  - final_calculated_price
  - selected_design_id/design_name
  - selected_design_image_id/design_image_name
  - text_elements
  - form_state (می‌تواند ماسک باشد)

---

## 15. AUDIT LOG (FUTURE)

### تصمیم

برای MVP الزامی نیست. می‌تواند آینده اضافه شود (برای HR audit و tracking تغییرات).

### جدول فوری (برای آینده Audit)

| نام ستون | نوع | null | پیش‌فرض | شاخص | یکتا | کاربرد |
|---------|------|------|---------|------|------|--------|
| id | INTEGER | NO | AUTO_INCREMENT | PRIMARY | YES | شناسه المنت |
| user_id | INTEGER | YES | NULL | FK | NO | کاربر مرتبط |
| action | VARCHAR(50) | NO | NULL | INDEX | NO | نوع عمل (create, update, delete) |
| entity_type | VARCHAR(50) | NO | NULL | INDEX | NO | نوع موجودیت (user, order, product) |
| entity_id | INTEGER | NO | NULL | INDEX | NO | شناسه متغیر |
| old_data | JSON | YES | NULL | INDEX | NO | داده قبلی (اختیاری) |
| new_data | JSON | YES | NULL | INDEX | NO | داده جدید (اختیاری) |
| ip_address | VARCHAR(45) | YES | NULL | INDEX | NO | IP üzerinden |
| user_agent | TEXT | YES | NULL | INDEX | NO | user_agent |
| created_at | DATETIME | YES | NULL | INDEX | NO | تاریخ ایجاد |

(فقط برای MVP به این جدول نیاز نیست، ولی برای audit در آینده لازم است)

---

## 16. INDEXES

### فهرست شاخص‌ها و دلیل وجود آن‌ها

#### ORDERS
- `idx_orders_user`: جست‌وجوی سفارشات یک کاربر
- `idx_orders_status`: جست‌وجوی سفارشات با status مشخص
- `idx_orders_created`: مرتب‌سازی هراس و پیدا کردن آخرین سفارش

#### ORDER_ITEMS
- `idx_order_items_order`: بازیابی آیتم‌های یک سفارش
- `idx_order_items_product`: بازیابی آیتم‌های یک محصول
- `idx_order_items_created`: مرتب‌سازی بر اساس زمان افزودن

#### CART_ITEMS
- `idx_cart_items_cart`: بازیابی تمام آیتم‌های یک سبد خرید
- `idx_cart_items_product`: بازیابی مکرر یک محصول در سبد خریدان
- `idx_cart_items_expires`: پاکسازی‌زدایی‌های ناکارآمد ۲۴ ساعته

#### PRODUCTS
- `idx_products_type`: فیلتر محصولات بر اساس type (bank / fuel / standard)
- `idx_products_active`: نمایش محصولات فعال

#### PRODUCT_COLOR_PRICES
- `idx_product_color_prices_product`: دریافت رنگ‌های قابل استفاده برای یک محصول
- `idx_product_color_prices_color`: جست‌وجوی محصول‌ها بر اساس رنگ
- `idx_product_color_prices_active`: نمایش فقط رنگ‌های فعال
- `UNIQUE(product_id, color_id)`: جلوگیری از تعریف تکراری یک رنگ برای یک محصول

#### COLORS
- `colors.name`: جست‌وجوی نام رنگ
- `colors.code_hex`: جست‌وجوی کد رنگ (اگر لازم باشد)
- `idx_colors_active`: نمایش فقط رنگ‌های فعال
- `idx_colors_order`: ترتیب نمایش در پنل ادمین

#### CATEGORY / GROUP / DESIGN
- ` cate_designs.name`: جست‌وجوی دسته‌بندی
- ` group_designs.cate_design_id`: ارتباط با دسته‌بندی (use standard foreign key)
- ` designs.group_design_id`: ارتباط با گروه
- ` designs.is_visible`: نمایش فقط طرح‌های فعال
- `design_images.design_id`: ارتباط با طرح
- `design_images.color_id`: ارتباط با رنگ (شکست variant)

#### COMPATIBILITY_RESTRICTIONS
- `idx_compatibility_design_image`: جست‌وجوی محدودیت‌ها بر اساس design Image
- `idx_compatibility_forbidden_color`: جست‌وجوی محدودیت‌ها بر اساس forbidden card color

#### USERS
- `users.phone`: جست‌وجوی کاربر بر اساس تلفن
- `users.email`: جست‌وجوی کاربر بر اساس ایمیل
- `users.role`: فیلتر کاربران بر اساس نقش

#### HOMEPAGE_SECTIONS
- `homepage_sections.section_key`: بازیابی یک بخش خاص
- `idx_homepage_sections_active`: نمایش فقط بخش‌های فعال
- `idx_homepage_sections_order`: ترتیب بخش‌های صفحه اصلی

---

## 17. DELETE BEHAVIOR (CASCADE / RESTRICT)

| جدول | Foreign Key | ACTION | دلیل |
|------|-------------|--------|------|
| group_designs | cate_designs | CASCADE | اگر دسته حذف شود، زیرگروه‌ها باید حذف شوند |
| designs | group_designs | CASCADE | اگر گروه حذف شود، طرح‌ها باید حذف شوند |
| design_images | designs | CASCADE | اگر طرح حذف شود، همه تصاویرش حذف شود |
| design_images | colors | CASCADE | اگر رنگ حذف شود، تصاویر آن رنگ حذف شود |
| cart_items | cart | CASCADE | اگر سبد حذف شود، آیتم‌هایش حذف می‌شوند |
| cart_items | products | RESTRICT | اگر محصول حذف شود، آیتم‌های سبد حذف نمی‌شوند (باید به admin اطلاع داده شود) |
| order_items | orders | CASCADE | اگر سفارش حذف شود، آیتم‌هایش حذف می‌شوند |
| order_items | products | RESTRICT | اگر محصول حذف شود، آیتم‌های سفارش حذف نمی‌شوند (Historical Integrity) |

### نکته مهم: RESTRICT برای محصولات

هر وقت محصول حذف می‌شود، آیتم‌های **سبد خرید** (cart_items) و **سفارشات** (order_items) حذف نمی‌شوند. این یک قاعده اصلی برای حفظ **Integrity Historical** است.

---

## 18. رابطه‌های کامل (Relationship Diagram)

```
users
  (1:N)
  └── orders
        (1:N)
        └── order_items
              (FIXES: embedded JSON by design)
        (FIXED: customizations into JSON fallback)

products
  (morphs)
  ├── bank products (via type='bank')
  │         (1:N)
  │         └── order_items (via product_id)
  │
  ├── fuel products (via type='fuel')
  │         (1:N)
  │         └── order_items (via product_id)
  │
  └── standard products (via type='standard')
                (1:N)
                └── order_items (via product_id)

colors
  (1:N)
  ├── BankProduct (via card_color_id; optional)
  ├── DesignImage (as design color)
  └── CompatibilityRestrictions (as forbidden color)

cate_designs
  (1:N)
  └── designs

designs
  (1:N)
  └── design_images

design_images
  (1:N)
  └── compatibility_restrictions

cart
  (1:N)
  └── cart_items

cart_items
  (1:1)
  └── products

homepage_sections
  (N-1)
```

---

## 19. نهایی انجام تخصیص Database

### جدول‌های الزامی (REQUIRED)

| جدول | ستون‌ها | شاخص‌ها | شروع | نکات |
|------|--------|---------|------|------|
| orders | id, user_id, total_price, status, customer_phone, customer_name, created_at, updated_at | user, status, created_at | ✅ | سفارشات فرضی |
| order_items | id, order_id, product_id, base_price, quantity, total_price, customization_json | order, product, created_at | ✅ | snapshot همه اطلاعات |

### جدول‌های اختیاری (OPTIONAL)

| جدول | ستون‌ها | شاخص‌ها | شروع | نکات |
|------|--------|---------|------|------|
| homepage_sections | id, section_key, title, content, image_path, is_active, sort_order | section_key, active, order | ✅ (در فایل PDF ساده نگهداری) | استفاده از JSON file یا جدول ساده |

### جدول‌های آینده (FUTURE)

| جدول | ستون‌ها | شاخص‌ها | شروع | نکات |
|------|--------|---------|------|------|
| roles | id, name, description | name | ⏳ (برای ادمین با نقش‌های اختصاصی) | فازهای امنیتی آینده |
| audit_log | id, user_id, action, entity_type, entity_id, old_data, new_data, ip_address, created_at | user, action, created_at | ⏳ | (برای Audit & compliance) |

### جدول‌های حذف شده (REMOVED)

- ~~`card_types`~~ → ادغام در `products.type`
- ~~`ank_card_data`~~ → ذخیره در `order_items.customization_json`
- ~~`uel_card_data`~~ → ذخیره در `order_items.customization_json`
- ~~`ustomizations`~~ → ادغام در `order_items.customization_json`
- ~~`art`~~ → جدا نگه داشته شده در فایل profile.layout برای محدودیت‌های قرن رنگی

---

## 20. پایان (Final Summary)

این طراحی دیتابیس ساده، ساخت‌یافتگی مناسب برای MVP را حفظ می‌کند و هم به حفظ عملکرد **historical orders** (snapshot در JSON) فراهم می‌کند و هم یک مدل **راحت** برای کاربرهای نهایی و سیستم ادمین.

بدون هیچ اضافه کردن جدول، بدون SQL پیچیده، و با رعایت کامل استانداردهای امنیتی امن هم قابل استفاده است. با حرکت از لحظه‌هایی که تصمیمات سیستم demand می‌شود، توسعه‌پذیری را نیز تضمین می‌کند.

بیش از طراحی نهایی در فایل‌های ARCHITECTURE_REVIEW.md.