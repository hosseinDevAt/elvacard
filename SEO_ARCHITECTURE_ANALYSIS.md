# SEO Architecture Analysis

# تحلیل و طراحی کامل SEO برای سیستم فروشگاه کارت فلزی

---

# 1. تحلیل وضعیت فعلی SEO پروژه

وضعیت فعلی پروژه از نظر SEO بسیار اولیه است.

مشکلات فعلی:

- URL structure استاندارد ندارد
- محصولات صفحه SEO-friendly مستقل ندارند
- دسته‌بندی‌ها فاقد slug و meta data هستند
- طرح‌ها (designs) صفحه indexable ندارند
- structured data وجود ندارد
- sitemap وجود ندارد
- robots.txt مدیریت نشده
- تصاویر alt/title ندارند
- canonical system وجود ندارد
- صفحات طراحی Livewire قابل index شدن هستند
- CMS صفحه اصلی هنوز SEO-oriented نیست
- سیستم مقاله/وبلاگ وجود ندارد
- internal linking architecture وجود ندارد
- breadcrumb وجود ندارد
- image optimization strategy وجود ندارد

نتیجه:

معماری فعلی برای گرفتن ترافیک ارگانیک گوگل کافی نیست و SEO باید به عنوان بخش اصلی معماری سیستم در نظر گرفته شود.

---

# 2. SEO محصولات

## هدف

هر محصول باید:

- صفحه اختصاصی قابل index داشته باشد
- meta data مستقل داشته باشد
- schema product داشته باشد
- URL استاندارد داشته باشد
- تصویر SEO-friendly داشته باشد
- canonical قابل مدیریت داشته باشد

---

## URL Structure پیشنهادی

```text
/products/metal-bank-card
/products/custom-fuel-card
/products/metal-necklace
```

URL ها باید:

- انگلیسی
- کوتاه
- قابل خواندن
- stable
- editable توسط admin

باشند.

---

## تغییرات دیتابیس برای products

### فیلدهای جدید SEO

| ستون | نوع | توضیح |
|------|------|--------|
| slug | VARCHAR(255) UNIQUE | URL محصول |
| meta_title | VARCHAR(255) | عنوان گوگل |
| meta_description | TEXT | توضیح گوگل |
| meta_keywords | TEXT NULL | اختیاری |
| canonical_url | VARCHAR(500) NULL | canonical custom |
| schema_type | VARCHAR(50) | Product |
| robots_index | BOOLEAN | index/noindex |
| og_title | VARCHAR(255) NULL | OpenGraph |
| og_description | TEXT NULL | OpenGraph |
| og_image | VARCHAR(255) NULL | تصویر اشتراک |
| seo_content | LONGTEXT NULL | متن SEO پایین صفحه |

---

## Product Image SEO

برای تصاویر محصول:

### پیشنهاد ساختار

جدول جداگانه:

```text
product_images
```

### فیلدها

| ستون | توضیح |
|------|--------|
| product_id | FK |
| image_path | مسیر تصویر |
| alt_text | alt SEO |
| title_text | title تصویر |
| sort_order | ترتیب |
| is_primary | تصویر اصلی |
| optimized_filename | filename SEO |
| format | webp/jpg |

---

# 3. SEO دسته‌بندی‌ها

## دسته‌بندی محصولات

نیاز به جدول:

```text
product_categories
```

محصولات استاندارد باید category داشته باشند.

---

## تغییرات cate_designs

cate_designs باید SEO-friendly شود.

### فیلدهای جدید

| ستون | توضیح |
|------|--------|
| slug | URL category |
| image_path | تصویر دسته |
| meta_title | عنوان SEO |
| meta_description | توضیح SEO |
| top_description | متن بالای صفحه |
| bottom_description | متن پایین صفحه |
| canonical_url | canonical |
| robots_index | index/noindex |

---

## URL Structure

```text
/designs/luxury
/designs/sport
/designs/minimal
```

---

# 4. SEO طرح‌ها (Design SEO)

## هدف

هر design باید:

- صفحه indexable داشته باشد
- در گوگل قابل جستجو باشد
- keyword-targeted باشد
- image SEO داشته باشد

مثال:

```text
/designs/dragon-metal-card
```

---

## تغییرات designs table

### فیلدهای جدید

| ستون | توضیح |
|------|--------|
| slug | URL design |
| description | توضیح کامل |
| meta_title | عنوان گوگل |
| meta_description | توضیح گوگل |
| canonical_url | canonical |
| robots_index | index/noindex |
| schema_type | CreativeWork/Product |
| main_image_alt | alt تصویر اصلی |
| seo_content | متن SEO |

---

## تغییرات design_images

### فیلدهای جدید

| ستون | توضیح |
|------|--------|
| alt_text | alt SEO |
| title_text | title تصویر |
| optimized_filename | filename SEO |
| is_primary | preview اصلی |

---

# 5. Dynamic Homepage SEO

## homepage_sections بازطراحی SEO

باید قابلیت:

- hero SEO content
- featured products
- featured designs
- FAQ section
- structured sections
- CTA sections
- rich content blocks

را داشته باشد.

---

## فیلدهای جدید homepage_sections

| ستون | توضیح |
|------|--------|
| slug | شناسه بخش |
| section_type | hero/banner/products/faq |
| title | عنوان |
| subtitle | زیرعنوان |
| content | محتوا |
| image_path | تصویر |
| button_text | CTA |
| button_link | CTA URL |
| meta_title | SEO |
| meta_description | SEO |
| sort_order | ترتیب |
| is_active | فعال |

---

# 6. FAQ System

## دلیل نیاز

FAQ برای:

- FAQ Schema
- گرفتن featured snippet گوگل
- long-tail keywords
- کاهش bounce

بسیار مهم است.

---

## جدول faq_items

| ستون | توضیح |
|------|--------|
| id | PK |
| question | سوال |
| answer | جواب |
| category | optional |
| sort_order | ترتیب |
| is_active | فعال |
| created_at | تاریخ |

---

## Schema

باید قابلیت تولید:

```json
FAQPage
```

داشته باشد.

---

# 7. Blog / Content Marketing

## تحلیل

سیستم مقاله برای SEO این پروژه بسیار مهم است.

دلیل:

- ورودی long-tail
- مقاله‌های آموزشی
- ranking روی keywordهای informational
- لینک داخلی به محصولات
- authority building

نتیجه:

سیستم blog لازم است.

---

## جدول articles

| ستون | توضیح |
|------|--------|
| id | PK |
| title | عنوان |
| slug | URL |
| excerpt | خلاصه |
| content | محتوا |
| cover_image | تصویر اصلی |
| author_id | نویسنده |
| status | draft/published |
| published_at | انتشار |
| meta_title | SEO |
| meta_description | SEO |
| canonical_url | canonical |
| robots_index | index/noindex |
| og_image | OpenGraph |
| created_at | تاریخ |

---

## URL Structure

```text
/blog/what-is-metal-bank-card
/blog/how-to-customize-fuel-card
```

---

# 8. Schema Markup Architecture

## صفحات نیازمند schema

| صفحه | schema |
|------|--------|
| product | Product |
| category | CollectionPage |
| article | Article |
| faq | FAQPage |
| homepage | Organization + WebSite |
| breadcrumb | BreadcrumbList |

---

## Product Schema

باید شامل:

- name
- image
- description
- brand
- offers
- price
- availability
- url

باشد.

---

# 9. URL Architecture

## ساختار نهایی پیشنهادی

```text
/
/products
/products/{slug}

/designs
/designs/{category-slug}
/designs/{design-slug}

/blog
/blog/{article-slug}

/faq
/about
/contact
```

---

## تصمیم مهم

URL ها باید انگلیسی باشند.

دلیل:

- استانداردتر
- share-friendly
- قابل فهم برای گوگل
- جلوگیری از encoding پیچیده

---

# 10. Sitemap Architecture

## نیاز

sitemap داینامیک ضروری است.

---

## صفحات داخل sitemap

### sitemap-products.xml

- همه محصولات active

### sitemap-designs.xml

- همه design pages

### sitemap-categories.xml

- همه categories

### sitemap-blog.xml

- همه مقالات منتشرشده

### sitemap-pages.xml

- صفحات static

---

## بروزرسانی sitemap

sitemap باید با:

- ایجاد محصول
- ویرایش slug
- انتشار مقاله
- فعال/غیرفعال شدن content

بروزرسانی شود.

---

# 11. Robots.txt Strategy

## نباید index شوند

```text
/admin
/dashboard
/cart
/checkout
/designer/*
/login
/register
/password/*
```

---

## دلیل

صفحات شخصی‌سازی Livewire:

- duplicate content ایجاد می‌کنند
- value SEO ندارند
- crawl budget مصرف می‌کنند

---

# 12. Performance SEO

## Core Web Vitals

بزرگ‌ترین ریسک:

- تصاویر design
- rerender های Livewire
- preview سنگین

---

## پیشنهادها

### تصاویر

- WebP
- lazy loading
- responsive images
- compression
- width/height explicit

### Livewire

- جلوگیری از rerender کل designer
- defer updates
- lightweight preview architecture

### Asset Strategy

- minified assets
- split bundles
- cache headers
- image CDN در آینده (اختیاری)

---

# 13. Internal Linking

## نیاز

سیستم لینک داخلی بسیار مهم است.

---

## پیشنهادات

### محصولات مرتبط

- محصولات مشابه
- طرح‌های مشابه
- رنگ‌های مشابه

### مقالات مرتبط

- مقاله → محصول
- مقاله → طرح
- مقاله → FAQ

### breadcrumb

```text
خانه > طرح‌ها > Luxury > Dragon
```

---

# 14. SEO Manager در پنل ادمین

## نیاز

ادمین باید بدون توسعه‌دهنده SEO را مدیریت کند.

---

## تنظیمات عمومی SEO

### جدول site_settings

| key | value |
|-----|------|
| site_title | ... |
| site_description | ... |
| og_image | ... |
| twitter_image | ... |
| robots_index | ... |
| sitemap_enabled | ... |
| organization_name | ... |
| organization_logo | ... |
| organization_phone | ... |

---

## SEO Manager UI

بخش‌های لازم:

- تنظیمات عمومی SEO
- sitemap
- robots
- OpenGraph
- structured data
- canonical management
- index/noindex management
- slug management

---

# 15. امنیت SEO

## جلوگیری از Duplicate Content

### canonical

همه صفحات dynamic باید canonical داشته باشند.

---

## صفحات noindex

- designer session pages
- cart
- checkout
- dashboard
- auth

---

## جلوگیری از Crawl Waste

صفحات personalization:

```text
/designer/*
```

نباید crawl شوند.

---

# 16. تغییرات مورد نیاز در مدل‌ها

## products

اضافه شود:

- slug
- meta_title
- meta_description
- canonical_url
- schema_type
- robots_index
- og_image

---

## cate_designs

اضافه شود:

- slug
- meta_title
- meta_description
- top_description
- bottom_description

---

## designs

اضافه شود:

- slug
- description
- meta_title
- meta_description
- seo_content

---

## design_images

اضافه شود:

- alt_text
- title_text
- optimized_filename

---

# 17. تغییرات مورد نیاز در Livewire

## Product Pages

- dynamic meta tags
- canonical generation
- schema injection

---

## Design Pages

- category filtering
- SEO-friendly routing
- lazy image loading

---

## Blog

- article rendering
- structured article schema
- related content blocks

---

# 18. Roadmap اجرای SEO

## Phase 1 — MVP SEO

- slug system
- meta titles/descriptions
- sitemap
- robots
- canonical
- basic Product schema

---

## Phase 2 — SEO Growth

- blog
- FAQ schema
- breadcrumbs
- internal linking
- image optimization

---

## Phase 3 — Advanced SEO

- article clusters
- related content automation
- advanced schema
- search console optimization
- performance tuning

---

# نتیجه نهایی

SEO باید بخشی از معماری اصلی سیستم باشد، نه feature فرعی.

این پروژه پتانسیل بسیار بالایی برای:

- SEO محصولات
- SEO طرح‌ها
- SEO محتوایی
- long-tail keywords
- image search
- FAQ snippets
- article authority

دارد.

معماری پیشنهادی:

- ساده
- قابل مدیریت برای کارفرما
- SEO-first
- قابل توسعه
- بدون over-engineering

و کاملاً سازگار با Laravel + Livewire است.
