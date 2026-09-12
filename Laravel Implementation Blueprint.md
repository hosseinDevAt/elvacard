# Laravel Implementation Blueprint

# نقشه اجرای نهایی پروژه Laravel

---

# 1. Laravel Project Setup

## نسخه‌ها

| تکنولوژی | نسخه |
|---|---|
| PHP | 8.3 |
| Laravel | 13 |
| Livewire | 4 |
| Alpine.js | 3 |
| Tailwind CSS | 4 |
| Vite | 8 |
| MySQL | 8+ |

---

## Composer Dependencies

### Required

```bash
laravel/framework
livewire/livewire
```

---

## Optional Future Packages

### SEO

```bash
spatie/laravel-sitemap
```

### Image Optimization

```bash
intervention/image
```

---

## NOT NEEDED

❌ Repository packages
❌ Admin generators
❌ Full CMS packages
❌ Heavy ACL packages
❌ SPA frameworks

---

## NPM Dependencies

```bash
tailwindcss
alpinejs
vite
```

---

## Environment Configuration

### APP

```env
APP_NAME=
APP_ENV=
APP_DEBUG=
APP_URL=
```

---

### Database

```env
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

---

### Cache

```env
CACHE_STORE=file
```

---

### Filesystem

```env
FILESYSTEM_DISK=public
```

---

## Storage Structure

```text
storage/app/public
├── products
├── designs
├── pages
├── articles
├── banners
└── settings
```

---

# 2. Migration Implementation Plan

## Migration Order

```text
1. create_users_table
2. create_colors_table
3. create_products_table
4. create_product_color_prices_table
5. create_cate_designs_table
6. create_designs_table
7. create_design_images_table
8. create_design_color_compatibilities_table
9. create_orders_table
10. create_order_items_table
11. create_homepage_sections_table
12. create_pages_table
13. create_menus_table
14. create_menu_items_table
15. create_faq_items_table
16. create_announcements_table
17. create_site_settings_table
18. create_article_categories_table
19. create_articles_table
```

---

## Enum Strategy

Laravel native backed enums.

Database:

```text
VARCHAR
```

NOT MySQL ENUM.

---

## Foreign Key Rules

### CASCADE

Use for:

- design → variants
- category → designs
- menu → menu_items

---

### RESTRICT

Use for:

- products referenced by orders
- colors referenced historically

---

### SET NULL

Use for:

- optional author relations
- optional user relations

---

# 3. Model Implementation

# User

## Fillable

```php
name
phone
email
password
role
address
```

---

## Casts

```php
email_verified_at => datetime
```

---

## Relationships

```php
orders()
```

---

## Scopes

```php
scopeAdmins()
scopeCustomers()
```

---

# Product

## Fillable

```php
type
name
slug
description
main_image
base_price
supports_chip_selection
engraving_config
meta_title
meta_description
canonical_url
robots_index
seo_content
is_active
```

---

## Casts

```php
engraving_config => array
robots_index => boolean
is_active => boolean
```

---

## Relationships

```php
productColorPrices()
orderItems()
```

---

## Scopes

```php
scopeActive()
scopeBank()
scopeFuel()
scopeStandard()
```

---

# Color

## Relationships

```php
productColorPrices()
designImages()
```

---

# ProductColorPrice

## Relationships

```php
product()
color()
```

---

# DesignCategory

## Relationships

```php
designs()
```

---

# Design

## Relationships

```php
category()
designImages()
```

---

# DesignImage

## Relationships

```php
design()
color()
compatibilities()
```

---

# DesignColorCompatibility

## Relationships

```php
designImage()
cardColor()
```

---

# Order

## Relationships

```php
user()
items()
```

---

# OrderItem

## Casts

```php
customization_json => array
```

---

## Relationships

```php
order()
product()
```

---

# HomepageSection

## Casts

```php
settings => array
```

---

# Page

## Scopes

```php
scopeActive()
```

---

# Menu

## Relationships

```php
items()
```

---

# Article

## Relationships

```php
category()
```

---

# 4. Enum Implementation

## Location

```text
app/Enums
```

---

# ProductTypeEnum

```text
bank
fuel
standard
```

---

# OrderStatusEnum

```text
pending
confirmed
processing
completed
cancelled
```

---

# PaymentStatusEnum

```text
unpaid
paid
failed
refunded
```

---

# ArticleStatusEnum

```text
draft
published
```

---

# MenuItemTypeEnum

```text
url
page
product
category
design
article
```

---

# HomepageSectionTypeEnum

```text
hero
banner
featured_products
featured_designs
faq
text_block
```

---

# Enum Helpers

Each enum:

```php
label()
options()
```

---

# 5. Service Implementation

## Location

```text
app/Services
```

---

# ProductService

## Responsibilities

- active products
- featured products
- slug lookup
- category filtering

---

## Methods

```php
getFeatured()
findBySlug()
getRelated()
```

---

# PricingService

## Responsibilities

- calculate final price
- resolve color pricing
- optional future extras

---

## Methods

```php
calculate()
resolveColorPrice()
```

---

# CompatibilityService

## Responsibilities

- validate variant compatibility
- return compatible variants

---

## Methods

```php
isCompatible()
getCompatibleVariants()
```

---

# OrderService

## Responsibilities

- order creation orchestration
- snapshot generation
- order calculations

---

## Methods

```php
createOrder()
generateSnapshot()
```

---

# SEOService

## Responsibilities

- meta generation
- canonical handling
- OG handling

---

# SitemapService

## Responsibilities

- sitemap generation
- sitemap refresh

---

# ImageService

## Responsibilities

- upload
- filename generation
- webp conversion
- image optimization

---

# CMSService

## Responsibilities

- homepage sections
- menus
- settings

---

# 6. Action Implementation

## Location

```text
app/Actions
```

---

# CreateOrderAction

## Flow

```text
Validate
↓
DB Transaction
↓
Create Order
↓
Create Order Items
↓
Generate Snapshot
↓
Return Order
```

---

# PublishArticleAction

## Flow

```text
Draft
↓
Publish
↓
Generate SEO
↓
Refresh Sitemap
```

---

# GenerateSitemapAction

## Flow

```text
Products
Designs
Pages
Articles
↓
Generate sitemap.xml
```

---

# 7. Livewire Architecture Implementation

## Location

```text
app/Livewire
```

---

# Structure

```text
Livewire
├── Front
└── Admin
```

---

# Front Components

```text
Front
├── Shop
├── Product
├── Designer
├── Cart
├── Checkout
├── Blog
└── CMS
```

---

# Admin Components

```text
Admin
├── Dashboard
├── Products
├── Designs
├── Orders
├── CMS
├── SEO
├── Blog
├── Users
└── Settings
```

---

# Component Responsibilities

## Product Components

```text
ProductIndex
ProductForm
ProductPricing
```

---

## Design Components

```text
CategoryManager
DesignManager
VariantManager
CompatibilityManager
```

---

## Order Components

```text
OrderIndex
OrderShow
OrderStatusManager
```

---

# 8. Product Designer Implementation

## State

```text
selectedProduct
selectedColor
selectedDesign
selectedVariant
textValues
textPositions
price
```

---

# Components

```text
Designer
├── ProductSelector
├── ColorSelector
├── DesignSelector
├── VariantSelector
├── Preview
├── TextEditor
└── AddToCart
```

---

# Livewire Responsibilities

✅ pricing
✅ validation
✅ compatibility
✅ snapshot

---

# Alpine Responsibilities

✅ tabs
✅ drag state
✅ resize interactions
✅ UI animations

---

# JavaScript Responsibilities

✅ text drag
✅ resize
✅ smooth transitions

---

# 9. Cart Implementation

## Strategy

✅ database cart

---

# Cart Storage

```text
carts
cart_items
```

---

# Cart Item Structure

✅ product snapshot
✅ customization snapshot
✅ final price
✅ expiration

---

# Expiration Logic

```text
expires_at = created_at + 24h
```

---

# Cart Cleanup

Scheduled cleanup:

```text
daily cleanup job
```

---

# 10. Checkout Implementation

## Flow

```text
Cart
↓
Customer Information
↓
Payment
↓
Create Order
↓
Confirmation
```

---

# Validation

✅ customer info
✅ pricing recheck
✅ compatibility recheck

---

# Transaction

✅ mandatory DB transaction

---

# Payment Callback Structure

```text
/payment/callback
```

Handles:

✅ verification
✅ payment status update
✅ order confirmation

---

# 11. Admin Implementation

## Dashboard

### Components

```text
DashboardStats
RecentOrders
QuickActions
```

---

# Products

### Tables

✅ products
✅ product_color_prices

---

### Forms

✅ general
✅ pricing
✅ customization
✅ SEO

---

# Designs

### Components

✅ categories
✅ designs
✅ variants
✅ compatibility

---

# CMS

### Components

✅ homepage
✅ pages
✅ menus
✅ FAQ
✅ announcements

---

# Blog

### Components

✅ article manager
✅ categories

---

# Settings

### Components

✅ general
✅ SEO
✅ social

---

# Permissions

✅ Laravel Policies only

---

# 12. Routing Architecture

## Frontend Routes

```text
/
/products/{slug}
/designs/{slug}
/blog/{slug}
/about
/contact
```

---

# Designer Routes

```text
/designer/bank-card
/designer/fuel-card
```

(noindex)

---

# Admin Routes

```text
/admin/dashboard
/admin/products
/admin/designs
/admin/orders
/admin/cms
/admin/blog
/admin/settings
```

---

# Route Files

```text
routes/web.php
routes/admin.php
```

---

# 13. Blade Architecture

## Structure

```text
resources/views
├── layouts
├── components
├── pages
├── products
├── designs
├── blog
├── cms
└── admin
```

---

# Layouts

```text
app.blade.php
admin.blade.php
```

---

# Shared Components

✅ cards
✅ buttons
✅ modals
✅ breadcrumbs
✅ alerts

---

# 14. Testing Strategy

## Feature Tests

✅ order creation
✅ pricing
✅ compatibility
✅ authentication
✅ cart flow
✅ checkout flow

---

# Unit Tests

✅ PricingService
✅ CompatibilityService
✅ SEOService
✅ SitemapService

---

# Livewire Tests

✅ Product forms
✅ Designer interactions
✅ Cart updates

---

# 15. Deployment Checklist

## Production Setup

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan storage:link
php artisan optimize
```

---

# Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

# Queue

Optional future:

```bash
php artisan queue:work
```

---

# Cron Jobs

```bash
php artisan schedule:run
```

Used for:

✅ sitemap refresh
✅ cart cleanup
✅ future announcements

---

# File Permissions

✅ storage writable
✅ bootstrap/cache writable

---

# Backup

✅ daily DB backup
✅ uploads backup

---

# Final Blueprint Summary

این Blueprint:

✅ Database architecture را پوشش می‌دهد
✅ Backend architecture را پوشش می‌دهد
✅ Frontend architecture را پوشش می‌دهد
✅ CMS architecture را پوشش می‌دهد
✅ Designer architecture را پوشش می‌دهد
✅ SEO architecture را پوشش می‌دهد
✅ Admin architecture را پوشش می‌دهد

و اکنون پروژه آماده:

✅ Migration writing
✅ Model implementation
✅ Service implementation
✅ Livewire development
✅ Frontend implementation
✅ Admin implementation

است.
