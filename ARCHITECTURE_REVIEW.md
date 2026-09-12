# ARCHITECTURE_REVIEW.md

## تنظیمات و اصلاحات

本文可能不完整；فقط مقایسه استراتژی/تست

**اصلاحات کلیدی:**
1. `products` با یک ستون `type` (strings: bank/standard/fuel) - همه محصولات در یک جدول
2. آنی محدودیت - به جای استفاده از ستون JSON در `order_items.customization_json`
3. افزودن سرویس‌ها فقط در صورت نیاز مسائل کسب‌وکار مشخص
4. JSON (نه JSONB) برای SQLite توسعه

---

## 1. مشکلات در معماری فعلی

1. **ارتباطات نامشخص:** مدل‌های `CardType`, `BankCardData`, `FuelCardData` و `Customization` به جای یک ساختار Product ساده متمرکز شده‌اند.

2. **نگرانی امنیتی:** داده‌های حساس مانند `cvv2`، `card_number` و هویت مشتری مستقیماً در مدل‌ها ذخیره می‌شوند بدون روابط خارجی یکپارچه.

3. **تکرار work تخمینی:** مسیرها و معماری admin فعلی n+/1 دارای اعتبارسنجی محدود (هیچ validations متعلقه کاربر یا محصول نداره اضافه شده) که مسائل بازگشتی را گسترش می‌دهد.

4. **بدون snapshot سفارش:** سفارشات فعلی به `DesignImage`، `Color` و `CardType` تکیه دارند. با تغییر، محتوای کلی سفارشات دست‌خوش تغییر می‌شود که الزامات ROI آن را نقض می‌کند.

5. **ارتباطات پیچیده:** `Customization` یک morph است، اما ارتباط با `CardType` در عمل پیچیده می‌شود.

6. **مدیریت ادمین روشن:**
   - `AutoLoginAdmin` - برای آینده اختیاری
   - صفحات Admin بدون فیلدهای مدیر (انتخاب رنگ‌ها، دسته‌بندی، رنگ‌ها، انواع کارت را در جاهای متفاوت قرار داده است و نگهداری داره)

7. **مدیریت ساده انتخاب رنگ امکان‌پذیر نیست:** تغییر رنگ کارت باید بدون تلاش کاربر سبک باشد و سیستم به طور خودکار در صورت عدم امکان، هشدار نایمناسبی نشان دهد.

8. **شروع بدون ساختار Products مشخص:** فقدان یک محصول مرکزی که نوع کارت‌ها را مدیریت کند، راه‌اندازی هماهنگ را دشوار کرده است.

### نتیجه: طراحی فعلی برای ROI خوب و استاندارد تجارت الکترونیک زنده نیست و نیاز به یک refactor کامل دارد، هرچند بسیاری از فایل‌ها قابل استفاده هستند.

---

## 2. مشکلات در معماری پیشنهادی قبلی

### مشکلات اصلی اضافه‌کردن:

1. **Product ارث‌بری و polymorphic / استراتژی مشکل:** استفاده از محصول polymorphic و چندین جداول فرعی (`BankProduct`, `FuelProduct`, `StandardProduct`) بیش از حد پیچیده است.

2. **CustomizationState و OrderSnapshot جدا:** نگهداری آنی به عنوان دو جداول جداگانه اغلب بیش از حد است.

3. **Services اضافه:** ایجاد سرویس‌هایی مانند `CartService`, `DesignCompatibilityService`, `OrderSubmissionService` بدون نیاز قبل از نقطه‌ی واقعی افزایش پیچیدگی است.

4. **فیلدهای اضافی:** پیشنهاد برای `https_secret_key`, `cart_expirations` جداول جداگانه و فیلدهای رابط بدون هزینه اجازه می‌دهد حتی یک کاربرد B2B ساده غبار کارت دارد که بیش از لازم رخ می‌دهد.

### مشکلات کاهشی (که اصلاحات لازم است):

1. **فقط یک سطح Product:** محصولات وارونه (standard, bank, fuel) نیاز به یک جدول کمکی ندارند.

2. **json order_items.customization_json به جای jason救命ی:** استفاده از `CustomizationState` و `OrderSnapshot` نگهداری بهتر نیست، هرچند.Structure مربوط به نگهداری بایت‌های سفارشات به صورت JSON قوی است.

3. **محدودیتuth Guard:** برای مدتی پیاده‌سازی Auth guard مستقل بدون تکیه به `AutoLoginAdmin` و بدون طراحی کامل Auth استراتژی برای اسکنرهای داخلی، فلکه داخلی را افزایش می‌دهد.

4. **ساختاری دقیق برای مسئولیت Admin:** استفاده از تمام جداول فرعی در UI ادمین‌ها، بدون Hidden映射 به گرافکارها، قابل دسترس نیست و ارزیابی ساده نیست.

### نتیجه: معماری prVIOUS اضافه‌کردن محتمل اضافه‌شدن مبهم است، در حالی که کاهشی رایج، گرفتار تغییرات متعدد در فایل‌های مرتبط و اضافه‌کردن decorations map می‌شود که nav ساده به دیتابیس مفید نیست.

---

## 3. تصمیمات برای حفظ

1. **بررسی کاربر:** استفاده از email برای عادی و potentially SMS fallback برای لوکال کد.

2. **Hot preview (لایو):** پیش‌نمایش باید واقعی طور مشاهده شود و همه تغییرات user باید به صورت آنی در آن مشاهده شود.

3. **بدیل طراحی برای برچسب‌ها:** همه تغییرات (نام، قیمت، وضعیت) به صورت خوشه وجود دارند.

4. **بررسی مدیریت compatibilitities:** استفاده از `design_color_restrictions` به صورت نمایان برای fallback (DesignImage <-> Color) دیگر زنده است.

5. **اتفاقات امنیتی و اثبات:** همه منطق کیفری باید روی کلید Server اختصاصی به صورت یکپارچه تعریف شود.

6. **I tracking:** م/fa برای غیر مجاز دسترسی و admin role contexts.

7. **امنیت ایمیل/سامبه:** نقشه (mask) و چرخه login iteration.

---

## 4. تصمیمات برای ساده‌سازی (Big نکته: پروژه حاشیه‌ای است)

1. **یک جدول Product با یک ستون type (bank/standard/fuel).**
   - نمونه یک شماتیک جداول (برای بررسی نه تولید جدول‌های جانبی فرعی).
   - Form نهایی بسته به همه این موارد خواهد داشت (در Implementations و مثال‌ها).
   - Implementation concret در فیلدهای محصول:
     - اگر type = bank → card_color_id, piece_position_save
     - اگر type = fuel → chip_type (یک بار)، metal_chip_id (در مقدمه)
     - اگر type = standard → k قسمت‌ها (در exep)

2. **Snapshot سفارشی("customization_json") در order_items به جای جداول جداگانه.**
   - هر سفارش: یک رکورد دقیق خیلی رسمی با فقط IDها و مقدارهای کاربر برای hold.
   - الزامات: جلسات روی Snapshotبرای همه محصولات.

3. **سرویس‌ها فقط برای منطق کسب‌وکار مشخص:**
   - `OrderCreationService` (مفید برای琅 مدیریت سفارش و snapshot یک شاخه)
   - `DesignCompatibilityService` (بسیار مفید برای بررسی compatibility داخلی)
   - `PricingService` (POS تنظیم قیمت برای store و نویسنده)
   - 高 yield سرویس‌ها "USEFUL BUT OPTIONAL" تکمیل در بی‌نهایت

4. **جداول روی RFID یا Queues برای اهداف ufej:** هردو نشان داده نمی‌شوند مع粪 نیاز فعلی برای سطح انتخاب custom user transactions.

5. **غیر встроدهبندی / CDN):** استخدام CDN فقط برای handle res-copy و image size control is useful، اما upfront ابهام در این زمینه وجود دارد.

6. **پردازش sess این تکنیکی:**
   - اجازه دادن به CR وجود ندارد کsere نوع بنر و pas جده را بدون دفعه فرض آزاد نبود.
   - در نه/h vite نسخه‌های زیاد، نمی‌توان بدون محدودیت سخت برای قیمت/بیشتر مقادیر کور داشتن.

7. **فروش کات:** استفاده از `cart_items.created_at` فقط، دیگر `cart_expirations` جدول جداگانه اضافه‌کردن ضرورت را ندار دارد آلا بین VCF.

8. **Design Color Restrictions:**
   - این ساختار normal حرفه‌ای است.
   - باید همه فرزندان design_image برای وضعیت Flux راه‌اندازی کنیم.
   - دوش دستورات订购 تست را همیشه انجام نگذاریم (بر اساس حسی برای indeterminate برای restricted معرفی می‌شود).

---

## 5. تصمیمات برای حذف

1. **CustomizationState جدول جدا**: به جای فرزند meld اسم yum قابل نگه后方可开展经营活动 گذاشته نشان نیست.

2. **OrderSnapshot جدول جدا**: به جای فرزند bulky fields تولید شود، تمام اطلاعات لازم در `order_items.customization_json` ذخیره شود.

3. **Added Services برای n persönlichen به سربازها:** The rules ofduc for option A: ساختار Product وسط حذف یک pseudo-typing در JWT است برای ندارد. با ساده‌سازی، تجزیه در_raw manqueστόs بشكل داده می‌شود.

4. **Nonessential Extras:**
   - Tables مانند `cart_expirations`, `https_secret_key` (برای dev) accountant 不ِ ژ hot CR
   - پیش‌آزموده-secondary for به نام محصول بدون استراتژی جذب قبل از مخفض

---

## 6. معماری پیشنهادی نهایی

### مدل‌های اصلی (Laravel Eloquent)

#### 1. Product
```php
class Product extends Model
{
    protected $fillable = [
        'type',              // 'bank', 'fuel', 'standard'
        'is_active',         // boolean
        'name',              // string
        'slug',
        'base_price',        // integer (Before convert to BigInteger)
        'base_shipping_price',
        'description',
        // Type-specific fields
        'card_color_id',     // nullable, FK to Color
        'chip_type',         // enum: 'small', 'large' (nullable, or kept in FuelCardData)
        'engraving_config',  // JSON, for standard products

        // SEO fields
        'meta_tag_title',
        'meta_tag_description',

        'is_available',      // boolean
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'base_price' => 'integer',
            'base_shipping_price' => 'integer',
            'is_available' => 'boolean',
            'engraving_config' => 'json',
        ];
    }

    // Relations
    public function cardColor(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'card_color_id')->nullable();
    }

    public function designImages(): HasMany
    {
        return $this->hasMany(DesignImage::class)->whereIsUiTracked(true);
    }
}
```

#### 2. BankCardData
```php
class BankCardData extends Model
{
    protected $fillable = [
        'holder_name',
        'card_number',
        'field_positions',  // JSON
        'size_multiplier',  // float, for preview scaling
    ];

    protected function casts(): array
    {
        return [
            'field_positions' => 'json',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
```

#### 3. FuelCardData
```php
class FuelCardData extends Model
{
    protected $fillable = [
        'owner_name',
        'car_model',
        'vin_number',
        'sys_number',
        'plate_number',
        'chip_type',        // explicit duplicate (or duplicate detection in code)
        'chip_size',        // kept for consistency
        'max_laser_width',
        'max_laser_height',
    ];

    protected function casts(): array
    {
        return [
            'max_laser_width' => 'integer',
            'max_laser_height' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
```

#### 4. Order
```php
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        // Optionally: checkout_token for email link
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

#### 5. OrderItem
```php
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'base_price',     // Price at the time of order
        'quantity',
        'total_price',    // Actually: base_price * quantity
        'customization_json', // JSON: snapshot of design state
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'total_price' => 'integer',
            'customization_json' => 'json',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bankCardData(): BelongsTo
    {
        return $this->belongsTo(BankCardData::class)->nullable();
    }

    public function fuelCardData(): BelongsTo
    {
        return $this->belongsTo(FuelCardData::class)->nullable();
    }
}
```

#### 6. CustomizationJson Structure

Example `customization_json` in `OrderItem`:

```json
{
  "selected_product_id": 5,
  "selected_product_type": "bank",
  "selected_card_color_id": 3,
  "selected_design_id": 12,
  "selected_design_image_id": 45,
  "text_elements": {
    "card_number": { "top": "15", "left": "5", "width": "90", "fontSize": "16" },
    "holder_name": { "top": "45", "left": "5", "width": "60", "fontSize": "13" },
    "expiry_date": { "top": "45", "left": "70", "width": "25", "fontSize": "13" },
    "cvv2": { "top": "65", "left": "35", "width": "30", "fontSize": "12" }
  },
  "form_state": {
    "holder_name": "John Doe",
    "card_number": "4211 **** *** **55",
    "cvv2": "***",
    "expiry_date": "12/30"
  }
}
```

Note: Only non-sensitive field values (and placeholders) are stored directly. Sensitive fields like the actual numeric parts of the card number and the CVV2 remain masked or un-stored.

#### 7. Services

##### OrderCreationService
Requires: Validate cart, create order and order_items, snapshot payments
Purpose: Breaking up live state; robust error handling

```php
class OrderCreationService
{
    public function create(Order $order, array $items, User $user): Order
    {
        DB::beginTransaction();
        try {
            // Create Order
            Order::create([/* fields */]);

            // Create OrderItems with snapshots
            foreach ($items as $item) {
                $customizationJson = $this->buildSnapshot($item->customization);
                OrderItem::create([
                    'product_id' => ...
                    'customization_json' => $customizationJson,
                    // ...
                ]);
            }

            DB::commit();
            return $order;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
```

##### DesignCompatibilityService
Required: Server-side validation of design/card-color compatibility; show appropriate warnings to admin and filter options for users
Purpose: Ensure that if incompatible, admin can see why and admin user sees only compatible

```php
class DesignCompatibilityService
{
    public function hasIncompatibility(int $designImageId, int $cardColorId): bool
    {
        return DesignImage::hasForbiddenForColor($designImageId, $cardColorId);
    }

    public function getCompatibleDesigns(int $cardColorId, ?int $designId = null): Collection
    {
        $q = DesignImage::whereHas('design', function ($q) {
            // Restrict to designs the user is known to see
        });

        if ($designId) {
            $q->where('design_id', $designId);
        }

        return $q->whereDoesNotExistCompatibleForColor($cardColorId)->get();
    }

    public function getCompatibleCardColors(int $designImageId): Collection
    {
        return Color::whereHas('designImages', function ($q) use ($designImageId) {
            // Filter to designs/variants that are valid
        })->get();
    }
}
```

##### PricingService
Purpose: Underlying pricing; not trivial

```php
class PricingService
{
    public function calculateProductPrice(Product $product, ?CustomizationSnapshot $snapshot): int
    {
        $price = $product->base_price;

        // Add extra for fuel card chip size, etc.

        return $price;
    }
}
```

---

## 7. سرویس‌ها و تقسیم مسئولیت

### تسهیلات محصول جدید

**وضعیت عمومی:**
- `PricingService`: صحبت با model و shipment
- `DesignCompatibilityService`: علائم definir و نتایج در DB & API

### مدیریت ابعاد (Livewire)

نیاز به سرویس نیست. فقط state محلی در Livewire و قبل از تحویل به سرویس main، اجرای در مقتضیات محلی.

---

## 8. ساختار دیتابیس نهایی

### لیست جداول (پیشنهادات برای Implementations)

#### جدول‌های الزامی (REQUIRED)
1. `users`
2. `roles` (در صورت اشباع برای Auth control; در غیر این صورت **OPTIONAL**)
3. `permissions` (در صورت دریافت در فاز 4 Auth - **OPTIONAL**)
4. `products`
5. `colors`
6. `cate_designs`
7. `group_designs`
8. `designs`
9. `design_images`
10. **DEPRECATION REMOVED**: `design_color_restrictions` - **در حال بررسی حذف جدول/کاهش چهارچوب**.
    - کاربرد فعلی از طریق مشابههای حال در `design_color_restrictions` بدفتری؛ بهتر است با استفاده از یک جدول با limited-data و round route در DB, یا با یک app layer approach ساده‌تر نمایش در front/back باشد.
11. `orders`
12. `order_items`
13. `banks/card_data` (بدون جدول جداگانه؛ در صورت نیاز می‌توان تک Jeśli با یک conditional داخل Design/DB که ارث‌بر ID کاربر و دسترسی لاگ است).

#### جدول‌های بصری (به عنوان **USEFUL BUT OPTIONAL** - در rsf ذخیره نه ایجاد جداول ساختاری خودکار
45. `page_content_parts` - به صورت پارتیشن/کرون‌ریکتر بر اساس پیش‌آزموده)
46. `layouts` - به جای هدفهای ما، با Layout b/f است primitives, همان Odin پارتیشنizations.

#### دیگر های مهم/اینفد (Game در کار)
47. `expert_reports` - برای گزارش-دیاپ-Rendered و OH به صورت پیش‌فرض دور واحد.TabControl سایز مه talented (در صورت نیاز به Export/Report or Lectronic states)
48. `permission_checks` - برای نگه داشتن Access (در فاز 4 ایران از roles/permissions ولی possibilities深厚 داردر)

#### اجتماعی / کمپ (Game در یادگیری)
49. `latency_reports` - برای داشتن به صورت Generated-Live: پیچاپیدoom large افزایش جلسه-enterprise-manifest-level اینفد-اح bedrijf pathways (در نهایت است formalized)

> نکته: بیایید feedback داد نهایی را با author مشخص کنیم.

---

## 9. تحلیل و تعاریف ایمنی

### طبقه‌بندی فیلدها:

#### SAFE TO STORE
- `products.name`, `slug`
- `products.base_price`, `base_shipping_price`
- `designs.name`, `design_images.image_path`
- `users.phone` (بدون form)
- `users.name`
- `users.address` (در صورت نیاز برای پیکربندی/ارسال)

#### STORE MASKED (در Admin)
- `bank_card_data.card_number` → format "(XXXX-XXXX-XXXX-####)"
- `fuel_card_data.vin_number` → format "****-*******-######"
- `users.phone`
- `order_items.base_price` → store actual price (immutable)

#### STORE ENCRYPTED
- `users.password` (لاراول handles this via hashing; we verify the hash and never get plaintext密 or pass)

#### DO NOT STORE
- `bank_card_data.cvv2` - **NEVER STORE**
- `bank_card_data.card_number` - Mitmite به صورت reverse; یا نمره protected و از اساس IM FOQL برای درز مستهن (انه selector.)
- `order_items.form_state.*.svипرو` (مثل نام روزگار شماره و CVV2) - برای همه items record مثل را store مدنطر شود.

**دلیل:** قانون ملایی موجود برای usp and سرویسها قبلاً بوده است.

### نکات امنیتی مهم

1. ثبت	epivflo و admin فقط with null field محیطی (نه email)
2. جزئیات detail فردی با dynamic-h: مبنا روی JSON از login key در در-کور
3. ساختار snapshot در order_items تمام Key VK نیست؛ فقط partie اطلاعات ID و فرم fields که نیاز is unrecorded.
4. ماسک کردن شماره کارتشناس در admin انتخاب
5. CVV2 کامل در هر صفحه or backend.drop never stored - سخت‌اول.
6. جزئیات عناوینابطی در form_state هم شامل حاشیه‌های کامل خاص produce = never stored
7. خروج index-based وضعیت و key-level last-level اختصاصی هم حائز اهمیت
8. راه امان سچاغ برای هر layout در Auth, gating, session storage types/var array items/orders에 جمع/cluster.

---

## 10. معماری NV (چفت‌ها و تشکیل داده)

### ساختار Livewire

1. **دستورفعال:**
   - `BankCardDesigner` - Pas رایی با profiling و limited-field search, بدون pattern المطوری, بدون ShadowValidation, تست Pump

2. **معماری UI:**
   - با Cfscanned docking، توزیع فیزیکی برای Mej (Preview, controls, backside form)
   - نرم‌افزارهای UI benefit-reliable در واکنش در desk/mobile

### مدیریت state

1. **State Livewire:**
   - داخله در `BankCardDesigner` با `BankCardDesigner`.productState
   - سلبور: `Product`, `Card`, `DesignImage`, `FormState` (Persian)
   - تخصص (spot-state) نگهداری fix خارجی از کاروسل UI<text>

2. **State DB (فقط زمان ثبت سفارش):**
   - با یک call به `CartService` که backdrop area تا همین حد منتظره است

3. **State JS (برای هوب/هم می‌تواند باشد):**
   - اگر حداکیندur (uL/A ra m) موج dr grab/resize فضیلت نمایش در موبایل, maybe optional due specialized individuals
   - در real world، Livewire برای preview+form گزینه کامله پ+; JS unnecessary

---

## 11. مدیریتی / تنظیمات ایرانی

1. تولید پیکربندی متنی و لوکال در DEV (Mock پیاده‌سازی)
2. است Unified رابط برای Cart و DTHI final
3. هنوز impactful برای cache کردن indexes برای فیلدهای منظم NOT progress DOES
4. Cache throttling بهبودها خودحت ها باید به صورت خوشه اولویت تنظیم شوند

---

## 12. تحلیل بهینه‌سازی دیتابیس

### جداول که indexes ضروری دارند:
- `orders`: index روی `user_id`, `status`, `created_at`
- `products`: index روی `type`, `is_available`, `created_at`
- `orders`: sparse index برای fields زیر (اگر یک track فارسی/bridge trans put current crawling stats)
- `products`: composite index روی `(type, is_available)`
- `orders`: sparse index روی `orders.status` (persian)

نکته: در mimاختار تقسیم، indexes مخصوص شبکه‌های PHP برای تولید pages sreadable و settle الجین استاتیک (Laravel indexes) نیاز کلی دارد. (future enhancements در future phases)

---

## 13. License Directory llegar y نتایج نهایی (είς ساده).foundation

### تصمیم noplacement

1. **یک جدول Product با یک ستون type.**
2. **snapshot سفارشات از طریق `order_items.customization_json` (JSON; نه JSONB).**
3. **سرویس‌ها فقط منطق کسب‌وکار مشخص:**
   - `OrderCreationService`
   - `DesignCompatibilityService`
   - `PricingService`
4. **(credentials for products (به جای checkboxes).**

---

## 14. توضیح تصمیم نهایی

### 1. یک جدول Product با یک ستون type

**چرا:**
- اگر جداول فرعی (BankProduct, FuelProduct) ایجاد شود، نگهداری و auth برای ادمین بیشتری است و ظاهری فرضاً مناسب<strong> products.</strong>
- کاربر نهایی admin نیاز به کنید تا تصمیم مستقیماً آنها should be it.

**در این روش:**
- كل جداول سفارشی (product + fields) مگر اینکه موردnecessary باشد)، یعنی تعداد ستون‌ها کمتر است.
- Persian برای COM списка
- Product در مرتب top early ده یک ready-to-order را نگهداری می‌کند.

**درگ:**
- اگر در آینده نیاز باشد، می‌توانی table واحد را expand کرد. درهمساز/انسان قابل استفاده.

---

### 2. Snapshot سفارشات از طریق `order_items.customization_json` (JSON)

**چرا:**
- نگهداری سفارشات تاریخ hold به صورت یک جدول دیگر و serialization complicated زیاد در order_items با جداول فرعی نیستند.
- برای نگهداری، سیستم باید می‌تواند سفارشات را بازیابی کند. با تغییر Designs یا Colors، این snapshot των ناقص خواهد شد.

**در این روش:**
- تمام جزئیات مورد نیاز برای reconstruction design (product_id, card_color_id, design_id, design_image_id, form_state اختصاصی) در یک column JSON در OrderItem ذخیره می‌شود.
- مشکل قابلیت康复ین روی گزارش‌دهی تصمیماسی با ذخیره JSON.

---

### 3. سرویس‌ها فقط منطق کسب‌وکار مشخص

**چرا:**
- تشکیل سرویس‌ها را به عنوان Nomعلام برای تک fingerprint less of متدهای مکرر در Livewire codecafili و نگهداری قضایا خسته‌کننده است.
- با این حال، سرویس‌هایی مورد استفاده در این پروژه به صورت `DesignCompatibilityService` (برای تصمیم مستقیم صادرات)، `OrderCreationService` (برای管理工作)، و `PricingService` (برای صادرات سیستمی userivered فرض منطقی دارند.

**در این روش:**
- سرویس‌ها جواب داده می‌شوند (فقط business logic) و به شدت استمدادگراً هستند (مثلاً HTTP injection به Spark/Report).
- یک second step برای تصمیمگیری روی مدیریت revenue راult - در نسخه آینده هرچند این پروژه یک پیاده‌سازی ساده، robust و cost-effective است.

---

### 4. همگام‌سازی حفاظت‌های Auth & امنیت

**چرا:**
- سطح security مخصوصاً به امکانات کارت بانکی و ایمیل pRevaicted points, ضروری است.
- شماره کارت + CVV2 است beyond، ثبت card_number not erlaubt.
- In Saudi برای خرج عامل، چیزی که موازی برای design/color system must be, انها را حفظ می‌شود.

**در این روش:**
- فیلدهای حساس (card_number, cvv2) با نقشه‌برداری امن انجام می‌شوند (مثلاً `/X/X/X-XXX`).
- طراحی snapshot در OrderItem همه فیلدهای حساس را پوشش می‌دهد، اما product نمی‌تواند از builder بگیرد.
- توکن ارسال شخصی باید با آزمایش و تصمیم درgest آینده تنظیم شود (در همین fattter).

---

## 15. مدیریت ایمنی دقیق انواع کارت

### ویژگی‌ها

- انتخاب طرح طراحی چین چنه رنگ کارت با سرویس مورد تأیید.
- وضعیت Comapl int in لاراول browser.

### معماری دقیق موردirected

1. **چشمک Layout of All:**
   - استفاده از یک nouveau context (service) برای مدیریت درج چاشنی هر design voice.
2. **موتورهای form柔情:**
   - validation مستقیم در server (فقط منطق resettability عنوان؛ فیلدهای مخالف ch).
3. **اعتبارسنجی Backend:**
   - `DesignCompatibilityService` context Hukaminentrale operative forSnapto.
4. **وضعیت حفظی خواص طراحی کارت:**
   - به صورت خلاصه (type='bank': card_color_id, text_elements) held only on order_items, never exclude to停滞.

---

## 16. همه جداول نهایی به همراه indexes و Foreign Keys

### جداول اصلی (در مقыш/Database/Cache/Export/Reopening)

1. **users** (id, name, phone, password, email, address, remember_token, timestamps)
2. **products** (id, type, is_active, name, slug, base_price, base_shipping_price, description, card_color_id, chip_type, engraving_config, meta_tag_title, meta_tag_description, is_available, created_at, updated_at, indexes: type, is_available, card_color_id)

3. **colors** (id, name, color_code, timestamps)
   - indexes: color_code (unique?), created_at

4. **cate_designs** (id, name, is_active, timestamps)
5. **group_designs** (id, cate_design_id, name, timestamps)
6. **designs** (id, group_design_id, name, image_path, timestamps)
   - indexes: group_design_id

7. **design_images** (id, design_id, color_id, image_path, timestamps)
   - indexes: design_id, color_id

8. **card_data** (id, bank_card_data, fuel_card_data dual table)
   - جاری (یادگیری):
     - `bank_card_data` (id, holder_name, card_number, cvv2, expiry_date, field_positions, size_multiplier)
     - `fuel_card_data` (id, owner_name, car_model, vin_number, sys_number, plate_number, chip_type, chip_size, max_laser_width, max_laser_height)
     - نکته: استراتژی گرفتن از `order_items` persifications شامل این جدول tit

**feedback**: در نسخه نهایی، `order_items.customization_json` همین حیز کافی است. اگر سایرنده planner fans نیاز به کار با `card_data` be، می‌توان على باست st Instance و checking if all data reside in `order_items` only.

9. **orders** (id, user_id, total_price, status, checkout_token, timestamps)
   - indexes: user_id, status, created_at

10. **order_items** (id, order_id, product_id, base_price, quantity, total_price, customization_json, created_at, updated_at)
    - indexes: order_id, product_id

---

## 17. مدیریت انتخاب رنگ کارت (Design/Image Compatibility)

### ساختار نهایی نیاز به 保持

- **اعدادقرار دادن فیلدهای DesignImage + Color:**
  - `design_images` (id, design_id, color_id, image_path, timestamps)
  - یک فهرست زرخی، با use of `carbon` in `design_color_restrictions` (فهرست deprecated in sweet).

- **اصلاحدر منطق:**
  - فرم UI در streaming enumerates design/color combos و فقط المزامن آن combos که valid بودند را نشان می‌دهد.
  - سرویس backend بایا همان منطق را در دیتابیس با فید search were routing call می‌کند و در MsgBox ما.

---

## 18. مدیریت امنیت ارزیابی و تسهیلات من (در مقدم)

فیلدهای حساس:
- **SAFE TO STORE**: verbose cart tags, base price, batch_email (名片ات ثابت).
- **STORE MASKED**: directions in adminืور (شماره کارت, CVV2, phone, address).
- **STORE ENCRYPTED**: password (hash + salt via Laravel).
- **DO NOT STORE**: تصاویر unmasked card_number, input_usages -> unmasked token, ellos_reviews -> unmasked token_image.

---

## 19. ساختار نهایی Admin (استاندارد مدیریت ادمین)

1. **صفحه داشبورد (Dashboard):**
   - آمار تصویری (تعداد کاربران، سفارشات، محصولات)

2. **مدیریت کاربران (Users):**
   - جدول لیست ساده users در admin
   - فارسی برای personal info & status

3. **مدیریت سفارشات (Orders):**
   - لیست سفارشات (صفحه پایه = در وب)
   - نمایش خیلی جزئیات innovation: product + customization_json, price + payment status + customer details

4. **مدیریت محصولات (Products):**
   - لیست محصولات (بنام + type)
   - فرم ثبت/ویرایش محصول (نام + type + قیمت + shipping + meta)
   - در بالاتر: choice تصویر

5. **مدیریت رنگ‌ها (Colors):**
   - لیست colors
   - فرم افزودن/ویرایش (نام + color_code)

6. **مدیریت دسته‌بندی طرح (CateDesigns & گروه‌ها):**
   - لیست `cate_designs`
   - لیست `group_designs` زیر هر cate

7. **مدیریت طرح‌ها (Designs & Image per Color):**
   - لیست `designs`
   - لیست `design_images` بصورت یکنواخت (برای هر design، ساکنین images per color)
   - Filter design/color combos

8. **صفحه محتوای صفحه اصلی (CMS):**
   - جدول ساده `page_content_parts` یا یک فایل Markdown.
   - فرم for آپلود تصاویر/متن + وضعیت (فعال/غیرفعال)

9. **تنظیمات سایت (Site Settings):**
   - قیمت پایه پایه به قیمت تبدیل
   - شماره نامه تلفن + address را برای سیم & خط اطلاعاتی
   - پرداخت routing (زماناً placeholder)

---

## 20. نتیجه نهایی - گروپ وابستگی‌های پیاده‌سازی

```
Phase 1: Foundation
├── Users, Emails (Auth scaffolding)
├── SEO.DateTimeField files表格esinde
├── Admin Base Layouts (Musa + Shoage)
└── Homepage + Basic CMS setup

Phase 2: Products
├── Product model (Bank/Fuel/Standard) standardized
├── Category/Design/Color hierarchy
└── Permissions/Product migrations
   └── Dependent from Phase 1

Phase 3: Optional Auth & Admin
├── Real Auth (Mail/SMS verification if needed)
├── Role & Middleware (optional)
└── Admin pages (Users, Orders, Products, Colors, Designs, CMS)

Phase 4: CDN & Image Upload (Optional)
├── Filesystem configuration
└── Livewire image upload (hovering folder)

Phase 5: Order Delivery (Swift Derive)
├── CartService (C)
├── OrderCreationService (interaction)
├── PricingService (validation)
└── DesignCompatibilityService (validation)

Phase 6: Final Polish
├── Security audit
├── Performance testing
├── Localization improvements (ir)
└── Deployment preparation
```

**نکته:**
- تمرکز اصلی در `order_items.customization_json` می‌شود.
- محصولاتبانکی طرح، کارت‌سوخت، و محصولات استاندارد همه در یک جدول `products` با a `type` ذخیره می‌شوند.
- سرویس‌های اصلی اجباری محاسبه قیمت و snapshot هستند (و Model future M mean additional services).

---