# PROJECT_SPECIFICATION.md

## 1. Executive Summary

This document provides a complete specification for the Iranian Persian RTL e-commerce platform for custom metal bank cards and fuel cards.

### Business Purpose
A professional e-commerce website in Persian language focused on:

- Custom metal bank cards (customized with designs and colors)
- Custom metal fuel cards (customized with designs)
- Standard laser-customizable physical products (necklaces, bracelets, etc.)

### Target Audience
Iranian customers looking for personalized metal cards and standard engraved products.

### Tech Stack
- **Backend**: Laravel 13, PHP 8.3
- **Frontend**: Livewire 4, Vite 8
- **Styling**: Tailwind CSS 4 (RTL-first)
- **Database**: SQLite (dev), will migrate to MySQL/PostgreSQL (prod)
- **Authentication**: Laravel's native implementation

---

## 2. Current System Analysis

### 2.1 Existing Models and Relationships

#### Users
- Simple user model with `name`, `phone`, `address`, `password`
- Follows Laravel's standard `Authenticatable` trait
- HasMany relationship to `orders`
- No explicit role/permission fields yet

#### Orders & OrderItems
- `orders`: `id`, `user_id`, `total_price`, `status`
- `order_items`: `id`, `order_id`, `card_type_id`, `customization_id`, `quantity`, `price`
- Many-to-Many relationship through `customizations`
- NO order snapshot of customization state currently

#### CardTypes
- Early design: `type` (bank/fuel), `color_id`, `base_price`, `is_available`
- No pricing rules defined
- Limited product attributes

#### Color
- `id`, `name`, `color_code`
- Admin-managed card colors and design colors

#### Design Hierarchy
- `cate_designs` (categories)
  - `group_designs` (subcategories/variants)
    - `designs` (design templates)
      - `design_images` (color variants per design)
        - `design_color_restrictions` (compatibility with other card colors)

This hierarchy is well-designed but incomplete for the required workflow.

#### BankCardData
- `holder_name`, `card_number`, `cvv2`, `expiry_date`
- Added `field_positions` (JSON) for text positioning
- Incomplete - missing reference to `card_type` in this model
- No validation of sensitive data

#### FuelCardData
- `owner_name`, `car_model`, `vin_number`, `sys_number`, `chip_type`, `chip_size`
- Added `plate_number` recently
- Missing `customer` or `card_type_id` foreign key
- No customer-facing field validation

#### Customization
- `card_type_id`, `design_image_id`
- `morphs('customizable')` references `BankCardData` or `FuelCardData`
- Basic but incomplete for full order snapshot

### 2.2 Existing Livewire Components

#### BankCardDesigner (app/Livewire/Designer/BankCardDesigner.php)
- Step-based workflow: 1→7
- Preset `fieldPositions` array for text elements
- Position/size controls for `top`, `left`, `width`, `fontSize`
- Basic compatibility check via `isForbiddenOnColor()`
- Saves design but doesn't persist to cart
- Validation incomplete for real-world use

#### FuelCardDesigner (app/Livewire/Designer/FuelCardDesigner.php)
- Step-based workflow: 1→5
- Chip size selection (small/large)
- Design selection via category sorting
- Basic compatibility enforcement
- Saves design but missing integration with orders
- No cart integration

#### Admin Components
- `Dashboard`: Basic stats (orders, users, card types)
- `ColorManager`, `CateDesignManager`: CRUD for colors/categories
- `CardTypeManager`: CRUD for card types (incomplete)
- `OrderManager`: Basic user-facing (no customization preview)
- `UserManager`: Basic user list
- Missing extensive CRUD or preview capabilities

### 2.3 Existing Routes

#### Admin
- `/admin/dashboard`
- `/admin/colors`
- `/admin/cate-designs`
- `/admin/card-types`
- `/admin/orders`
- `/admin/users`
- Admin access controlled via `AutoLoginAdmin` middleware
- No authentication standardization yet

#### Public
- `/` (home)
- `/طراحی/کارت-بانکی` (bank card designer)
- `/طراحی/کارت-سوخت` (fuel card designer)
- `/ورود` (login) - hardcoded inline login logic
- `/پنل-کاربری` (user dashboard)
- **Missing**: Product pages, cart, checkout, authentication pages

---

## 3. Target Architecture

### High-Level Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface (Browser)                  │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │   Public     │  │   Admin      │  │  3rd Party APIs │   │
│  │   Site       │  │   Panel      │  │                 │   │
│  └──────────────┘  └──────────────┘  └─────────────────┘   │
│                      ↓  ↓  ↓                                   │
├─────────────────────────────────────────────────────────────┤
│                    Livewire Components                        │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │ Product List │  │ Designer     │  │  Admin UI       │   │
│  │ / Details    │  │ / Cart       │  │                 │   │
│  └──────────────┘  └──────────────┘  └─────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                      ↓  ↓  ↓
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │ Cart Service │  │ Order Service │  │ Design Service  │   │
│  │  (24h exp.)  │  │  (snapshot)  │  │   (compatibility│   │
│  └──────────────┘  └──────────────┘  │        rules)   │   │
│  ┌──────────────┐  ┌──────────────┐  └─────────────────┘   │
│  │ Pricing      │  │ Search       │  │ Export/Import   │   │
│  │ Service      │  │ Service      │  │                 │   │
│  └──────────────┘  └──────────────┘  └─────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                      ↓  ↓  ↓
┌─────────────────────────────────────────────────────────────┐
│                    Domain Model (Laravel Eloquent)           │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │ Products     │  │ Orders       │  │ Cart/CartItems  │   │
│  │ /Variants    │  │  /Items /    │  │   /Expiration   │   │
│  │              │  │   /Snapshots │  │                 │   │
│  └──────────────┘  └──────────────┘  └─────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                      ↓  ↓  ↓
┌─────────────────────────────────────────────────────────────┐
│                  Database (SQLite/MySQL)                     │
│  Users, Roles, Permissions, Products,                      │
│  CustomizeState, Cart, Orders, Metadata                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Domain Model

### Refactoring Notes

#### 1. Products Architecture

**REMOVES**: Direct use of multiple tables for product variants.

**INTRODUCES**: A unified Product model that covers both card types and standard products.

```php
class Product extends Model
{
    // Fields: id, type (serial/standard/card/bank/fuel)
    // Type enum: 'serial', 'standard', 'bank', 'fuel'
    // type='serial' → belongsTo: StandardProduct
    // type='bank'   → belongsTo: BankCardProduct
    // type='fuel'   → belongsTo: FuelCardProduct
}
```

**StandardProduct**
- Instructions: None yet (future laser-engraving products)
- Preview requirements: Basic image placeholder

**BankCardProduct**
- Extends Product
- card_color_id FK to Color
- base_price, base_shipping_price
- is_available
- aspect_ratio hints (e.g., "1.58:1" for card)

**FuelCardProduct**
- Extends Product
- chip_type_enum ('small', 'large')
- chip_type 1-to-1 via MetalChip
- chip_size hints for preview

#### 2. Customization State

**REMOVES**: Current `Customization` model's direct link to `CardType` and `DesignImage`.

**INTRODUCES**: CustomizationState:

- customizable_id, customizable_type (morphs → BankCardProduct/CustomerOrderItem)
- selected_product_id
- selected_card_color_id (nullable, only for bank cards)
- selected_design_id (nullable)
- selected_design_color_id (nullable)
- text_elements (for text positioning, stored as indexed array per element)

CustomizationState is a creation-time snapshot of the customization.

#### 3. OrderSnapshot

**REMOVES**: No current order/customization snapshot.

**INTRODUCES**: `OrderSnapshot` (order_snapshot):
- order_id FK
- selected_product_id
- selected_card_color_id (nullable)
- selected_design_id (nullable)
- selected_design_color_id (nullable)
- text_elements_snapshot (persisted configuration for bank cards)
- product_option_snapshot (e.g., chip_size if available)
- customization_state_checksum (hash of CustomizationState JSON)
- created_at, updated_at

If Product, Color, or Design change later, this immutable record preserves exactly what was ordered.

The `Product` or `Order` object links to the `OrderSnapshot`.

---

## 5. Database Architecture

### 5.1 Products & Variants

#### products (pivotal)
Primary entity. Type determines which variant table to use.
- id (primary key)
- type (enum: 'serial', 'standard', 'bank', 'fuel')
- is_active
- created_at, updated_at

#### standard_products
If type='standard', holds standardized base info.
- id (FK to products), name, slug, description, images (json: [{url, label}]), price, base_shipping, options (json: [{name, values, selected}]), meta_tag_title, meta_tag_desc, is_available, slug_update_request (nullable, uuid for worker).upper-case columns: a placeholder for future approvals; see core logic in MODEL sync later.

#### bank_products / bank_card_products
If type='bank':
- id (FK), display_name (e.g., لیست‌دار پلاسیک)
- card_color_id (FK to colors), base_price, base_shipping
- aspect_ratio (e.g., '1.58:1'), max_text_elements, instruction_preview
- supports_colored_instructions, chipless (bool), enrollment_required (bool), enrollment_order_id (FK to orders, optional)
- is_available, created_at, updated_at

#### fuel_products
If type='fuel':
- id (FK), display_name (e.g., کانتینر 30 لیتری)
- chip_type (enum: 'none', 'small', 'large'), chip_required (bool)
- base_price, base_shipping, default_chip (maybe a MetalChip record)
- is_available, created_at, updated_at

#### metal_chips
Chip specification linked to products when chip_type is not 'none'.
- id, product_id, chip_type (enum), board_material, digital_interface, specs (media), is_available, cost_extra, created_at, updated_at

#### design_categories, designs, design_images remain as-is.

#### users, roles, permissions (optional)
Add if role-based admin control is needed.

#### cart, cart_items

#### cart_expirations (optional, simplified approach)
Either:
- Keep cart_items.created_at and check age (simple)
- Or add a dedicated cart_expiration record

#### orders & order_items remain with update to add snapshot FK.

#### order_snapshots (new)
- id, order_id (FK)
- selected_product_id
- selected_card_color_id (nullable)
- selected_design_id (nullable)
- selected_design_color_id (nullable)
- text_elements_snapshot (nullable, json)
- product_option_snapshot (nullable, json)
- customization_state_checksum (nullable, nullable because Format/type may vary)
- created_at, updated_at

#### user_address_primary (new)
- id, user_id (FK), type ('billing', 'shipping'), is_primary, label, address_lines, city, postal_code, phone, created_at, updated_at

#### https_secret_key (new)
- id, key_hash (SHA-256), expiry_hours, is_valid, created_at, updated_at

---

## 6. User Flows

### 6.1 Public Homepage

Visual sequence:
1. Hero/banner (loaded from CMS)
2. Promotional banners (carousel)
3. Category sections: کارت بانکی, کارت سوخت, محصولات عمومی
4. Featured products (random or m-content)
5. Special offer banner
6. CTAs to designer

Admin-managed; idempotent updates via CMS.

### 6.2 Bank Card Designer UX

Intermediate: First introduction on home refers to a product landing page. After selection, landing page offers a call to step into the designer (bank card). The designer then collects:
1. Card selection →
2. Color →
3. Category →
4. Design →
5. Design color →
6. Live preview (front) → Backside → Positioning/ρesizing → Proceed.

Prior to final add-to-cart/UI is called, the system shows a retention window detail. The completion of the designer workflow is described below in more detail.

---

## 7. Customization Architecture

### 7.1 Customization State

Flight path (snapshotting):
1. Begin with CustomizationState creation.
2. Validate current design/color/product selection and compatibility.
3. Publish event documenting critical decisions to logs and snapshots.
4. Persist to database.

Persisted as JSON and referenced from OrderSnapshot customizations.
The CustomizationState model holds the immediate state; OrderSnapshot stores a snapshot hash and selected IDs.

### 7.2 Compatibility Logic

Domain model approach:
- DesignImage.hasCompatibility(Color) returned as a boolean.
- frontends use this to hide/show incompatible options.
- Cart/Order persist only compatible options.
- A CompatibilityChecker class produces details for admin incompatibility where available; this gives admin actionable details.

### 7.3 Text Positioning

Configuration for bank cards:
- text_elements (json: {field: {top, left, width, fontSize, max_width, min_width, top_range, left_range:, pixel hint}})
- When setting, validate against live bounds and ranges.
- Restrictions: min/width/height are safe boundaries.

Domain-level checks enforce that:
- text fits within element visibility boundaries.
- reasonable limits are enforced before updates.

---

## 8. Bank Card Designer Specification

### 8.1 Workflow Steps

1. Choose Card (call to selecting a bank card product from landing)
2. Choose Card Color (from available bank colors)
3. Choose Category (filter designs)
4. Choose Design (within category)
5. Choose Design Color (from compatible variants)
6. Live Preview (front)
7. Card Backside (instruction input)
8. ↺ Go Back (revisit any previous step)
9. ↻ Edit (move among steps up to current)
10. Final Add-to-Cart (pop a retention window detail)

On backside reveal, inputs should follow a card-back instruction template appropriate for the card type.

### 8.2 Layout

Preferred two-column:
- Left: Live card preview
- Right: Controls (colored cards, category, design, backside fields)

### 8.3 Responsive Behavior

- Desktop: row layout
- Tablet and mobile: stack preview on top, controls below

### 8.4 State Preservation

Changing card color does NOT destroy:
- Selected design
- Entered card information
- Field positions
- Element sizes

If incompatibility is detected before committing, warn with a visible message in Persian and undo the change.

### 8.5 Validation

Required fields: holderName, cardNumber.
Optional: cvv2, expiryDate. No mixins that stray from components; these are specific to input fields.

---

## 9. Fuel Card Designer Specification

### 9.1 Workflow Steps

1. Choose Fuel Card (from landing/product selection)
2. Choose Chip Size (small/large, pinned to Products)
3. Choose Category (filter designs)
4. Choose Design
5. Choose Design Color (from compatible variants)
6. Live Preview (front)
7. Backside Information (structured or table-style)
8. ↺ Go Back (revisit any step)
9. ↻ Edit (move back)
10. Add-to-Cart

Design colors are limited to white for fuel cards; card color UI hidden.

Positioning logic is not required; fields are static and side-by-side with the preview.

---

## 10. Standard Product Catalog

### 10.1 Product List

Standard order:
- List page with pagination, filters by category, price range
- Featured items highlighted
- Inline preview images

### 10.2 Product Details

Engraving instructions: Use a visible "This product can be engraved" badge and instructions in Persian.

### 10.3 Price Model

TODO: Determine pricing structure (fixed, tiered per item, volume).

---

## 11. Cart Architecture

### 11.1 Cart Retention

Policy: 24-hour fixation window from item addition.
Exact implementation:
- Use created_at on cart_items for age check.
- Show a retention warning before add-to-cart.

If a cart stays beyond 24 hours, items are pending removal. When a customer adds to cart:
- Check creation timestamps; if > 24h, ask customer to recreate.
- Optional: alert existing cart when cart token is created.

### 11.2 Cart Items

cart_items:
- id
- user_id (nullable for anonymous)
- cart_id
- product_id
- customization_snapshot (nullable, json only IDs — no PDUs)
- quantity
- unit_price
- created_at
- internal_notes (optional, auto-generated from snapshot checksums)

assigned cart_id ensures known-customer association if authenticated; otherwise user_id is null.

### 11.3 Session vs Database

- Anonymous users without account: Session-cart (simple JSON).
- Authenticated or explicit cart ID: Database-cart (for future integration with email/ephemeral links).

---

## 12. Order / Snapshot Architecture

### 12.1 Order Creation Flow

1. Cart verification → monetize →
2. Create Order record →
3. Persist customizations snapshot in OrderSnapshot →
4. Clean up cart (or mark as used) →
5. On error: want a rollback visual for admin and restore customer to steps (from design/fields).

### 12.2 Snapshot Immutability

OrderSnapshot has these columns:
- selected_product_id
- selected_card_color_id (nullable)
- selected_design_id (nullable)
- selected_design_color_id (nullable)
- text_elements_snapshot (nullable, json)
- product_option_snapshot (nullable, json)
- customization_state_checksum (nullable)
- created_at, updated_at (defaults: created_at only)

If Product/Color/Design change later, this record remains unchanged internally, but links are stored as references (e.g., product_id, design_id) — not as fully-materialized strings.

### 12.3 Data Protection

Handle sensitive fields safely:
- Mask card_number, phone, email in admin orders (display pattern).
- store and/or transmit securely; no explicit encryption code yet.
- Admin orders: card_number like 'XXXX-XXXX-XXXX-1234'.

---

## 13. Admin Architecture

### 13.1 Product Management

Pages: /admin/products, /admin/products/create, /admin/products/{id}/edit.

CRUD operations:
- Name, price, shipping, description, images, slug, is_active, mk_keywords, meta_title, meta_desc, approval workflows (applies to metadata and approvals)
- For Standard/Serial products: Static configuration
- For Bank/Fuel products with color/size: Connection to Color/MetalChip.

Search: by name or slug, paginated results.

### 13.2 Design Management

Design color restriction UI:
- A mapping editor showing which design color variants are valid or blocked for which card colors.
- Optional: show compatibility checker results from backend.

Product design selection:
- When a product is edited, the admin can select which designs are available.

### 13.3 Order Management

Columns: ID, customer_name, order_ref, total, status, created_at, customizations table (linked by collapse or drill-down), customer intent.

Actions: Status change (pending→confirmed→processing→shipped→delivered→cancelled), royalty/fee calculation, reprint order details.

Preview:
- Render live preview with snapshot data without storing rendered images.

Export: CSV or admin-side routes for downloading order history.

### 13.4 User Addresses

User basic info (name, phone, address)
- One primary address (user_address_primary), with is_primary

Create/Update/Delete addresses per user.

### 13.5 Role-Based Admin (REQUIRES DECISION)

Decide whether to adopt User and Permission models or rely on middleware-based admin flag.

---

## 14. Homepage / CMS Architecture

### 14.1 CMS Approach

Option A: Simple table-based.
- homepage_sections (slug, title, content, image_path, sort_order, display_from, display_until)
- Homepage queries these sections and renders in order

Option B: Markdown/Livewire.
- Frontmatter on a blade file

Recommended: Option A for admin simplicity.

### 14.2 Admin UI for CMS

Create/Edit/Delete sections:
- Show live preview (cached or reactive)
- Set title, content, image, dates
- Drag-and-drop ordering (future)

---

## 15. Authentication / Authorization

### 15.1 Authentication

Use Laravel's default guard for now.
- login, register, password reset via Email (preferred) or Phone (optional, integrate with SMS provider).
- email delivery via SMTP or Laravel Mail 2FA provider (REQUIRES DECISION).

### 15.2 Authorization

Four-tape access levels:
- Guest
- User (customer)
- Admin
- System (background jobs)

Decision needed: Use Spatie permissions or custom middleware.

### 15.3 Admin Access

- Admin login from user redirect attribute via login_form (or separate)
- Enforce role check for admin routes

---

## 16. Security Architecture

### 16.1 CSRF Protection

- Use Laravel form controllers or @csrf
- Livewire CSRF automatically handled

### 16.2 Validation

- Use Form Request classes for orders, comments, account updates
- Validate card_number, phone, email conform to real-world rules per spec

### 16.3 Authenticated Data Access

- Enforce user_can_view_order(user_id, order_id)
- Enforce admin_has_access(admin_id, order_id)

### 16.4 File Upload Security

- Validate file types, sizes
- Storage/CDN placement
- Use LF policies

### 16.5 Authorization Inside Livewire

- Use authorize() and gate() given roles
- Do not rely on frontend controls alone (show/hide, computed only)

### 16.6 SQL Injection

- Use Eloquent and Laravel query builder; param queries safe
- Avoid raw SQL in controller/Livewire

### 16.7 XSS Prevention

- Use Blade @if directives and {{{ }}} appropriately
- Set report_path in config for mediadog-style events (optional)

### 16.8 Order Access Control (admin)

- Ensure admin order pages query only orders they should see

### 16.9 Sensitive Card Data

- Do NOT store CVV2 in persistence.
- Mask card_number field in admin.
- Enforce max 3 attempts for OTP or PIN; log attempts.

### 16.10 Logging

- Log types: authentication, authorization failure, errors
- Do not log card_number, personal email, or any raw PII in logs

---

## 17. UX Requirements

### 17.1 Designer UX

Primary goals:
- Minimize number of forms.
- Show result live after minimal decision points.
- Help the user see:
  - Which step they are in.
  - What is currently selected.
  - Whether the chosen combination is compatible (shows error or disables option).
- Give Persian-only prompts.

### 17.2 responsive Behavior

- Desktop: side-by-side preview + controls
- Tablet/mobile: Stack preview above controls
- Touch-friendly ranges and buttons

### 17.3 Admin UX

Form-based admin panel:
- Clear labels.
- Persian text.
- Means to quickly find items (search + filters).
- Confirm before destructive actions (delete product/design).
- Empty states where appropriate.

---

## 18. Responsive Requirements

Media query breakpoints (Tailwind v4 equivalent):
- sm: 640px
- md: 768px
- lg: 1024px
- xl: 1280px
- 2xl: 1536px

Design guidelines:
- Form input minimum labels for usability.
- Preview sizing uses percentages or flex-basis to adapt.
- Safari browser compatibility until alternative is preferred (historical note from your cache: Safari 18+/iOS support can be improved, but underlying guidelines remain flexible).

---

## 19. Performance Considerations

Identify bottlenecks and optimization opportunities:

- Live preview:
  - Image sizes: Serve optimized (WebP/AVIF), lazy load
  - CDN for design images
  - Non-destructive image manipulation (store reference, resize on-demand)

- Product listings:
  - Pagination (not infinite scroll)
  - Index on type, name, price where appropriate
  - Eager load categories and previews to avoid N+1

- Cart:
  - In-memory caching for session-based cart with user_id-based lookups later
  - Remove completed items at vendor/partner fetch (optional, until business decision)

- Admin:
  - Add index on type, is_active, created_at for slower admin queries
  - Queue summary stats to reduce N+1

- Design image compatibility:
  - Cache compatibility checks via database constraints or in-DB query results
  - Avoid repeated color-restriction fetches in the same request

---

## 20. Implementation Roadmap

### Phase 1: Foundation & Authentication

**Objective:** Secure signup/login, user model enhancements, homepage structure.

**Actions:**
- Upgrade auth scaffolding (Email-based + 2FA optional).
- Add roles system or admin flag middleware.
- Create Users and Homepages tables.

**Dependencies:** None

**Acceptance Criteria:**
- User can register (email + validate).
- User can verify email/pwd (at least password).
- Admin login via dedicated panel.
- Homepage loads with basic sections.

**Models:** User, Role (if any), Homepages.

**Views:** Home, Login, Register, ResetPassword (requires setup).

### Phase 2: Core Domain & Products

**Objective:** Product model, variants, CardType cleanup, splits.

**Actions:**
- Introduce Product base model.
- Split: products, standard_products, bank_products, fuel_products.
- Add MetalChip (only for chip_type != 'none').
- Consolidate existing Color and Kensington/Chip + colors logic.

**Dependencies:** Phase 1.

**Acceptance Criteria:**
- Product CRUD complete across types.
- Proper foreign keys (FK) and enums.

**Models:** Product, StandardProduct, BankProduct, FuelProduct, MetalChip.

### Phase 3: Homepage / CMS

**Objective:** Homepage dynamic content.

**Actions:**
- Add homepage_sections.
- admin/index: Add homepage manager UI (list, sort, create, edit).

**Dependencies:** Phase 1.

**Acceptance Criteria:**
- Admin can update fully.
- Homepage reflects admin content.

**DB:** homepage_sections.

### Phase 4: Authentication & Admin Access

**Objective:** Standard login/register/logout / Role-aware admin.

**Actions:**
- build/auth: login_form blade and livewire; finalize devices and tokens.
- admin/auth: portal and email-based login.
- Role system (Spatie or custom).

**Dependencies:** Phase 1, Phase 2.

**Acceptance Criteria:**
- Pub. login and register work; requires(/[AU] email).
- Admin access via portal; gate authorizes order, etc.

### Phase 5: Bank & Fuel Products

**Objective:** Bank cards, fuel cards, pricing, + plan to control CardType via Bank/FuelProducts; design match.

**Actions:**
- Bank/Fuel Products CRUD.
- Pricing + shipping (base_price, base_shipping).
- Is available; association with color/chip_type.

**Dependencies:** Phase 2.

**Acceptance Criteria:**
- Admin can set base prices and shipping; design combos.

**Migration:** products (nesting: mix of serializers, colors, chip_type, colors).

### Phase 6: Cart Architecture

**Objective:** Cart + retention window.

**Actions:**
- cart, cart_items (choice: session-based or DB-based).
- 24h retention check at add-to-cart.
- Retention warnings.

**Dependencies:** Phase 1, Phase 2 (Products), Phase 5.

**Acceptance Criteria:**
- Cart expiration detection; client notification at add-to-cart.

**Models:** Cart, CartItem (or add cart_id to cart_items).

### Phase 7: Bank Card Designer

**Objective:** Bank card designer UX (stepwise, compatibility, state handling).

**Actions:**
- Landing page for Bank cards.
- Designer step layout (preview, controls, backside fields).
- Compatibility enforcement.
- meta/meta-save (no changes on reasoning).

**Dependencies:** Phase 5 (Products), Phase 6.

**Acceptance Criteria:**
- Bank card designer single-page steps, live preview, compatibility patch.
- Final customization data saved (no PDUs in snapshot).

### Phase 8: Fuel Card Designer

**Objective:** Fuel card designer UX.

**Actions:**
- Fuel product landing + designer (chip selection, categories, designs, live preview).
- Backside info (structured fields, no drag-drop).
- Compatibility enforcement.

**Dependencies:** Phase 5 (Products), Phase 6.

**Acceptance Criteria:**
- Fuel card designer workflow follows spec; cart acceptance.

### Phase 9: Order / Snapshot

**Objective:** Orders with Snapshot is the main feature this request.

**Actions:**
- OrderSubmissionService; persist Order + OrderSnapshot.
- OrderSnapshot columns.
- Pagination for admin orders; show snapshot; product/type/design ids.
- Add cart_items chain in CN and DB context.

**Dependencies:** Phase 6, Phase 7, Phase 8.

**Acceptance Criteria:**
- Order snapshot accurate per spec; admin sees full customization via snapshot fields/config.

**Models:** Order, OrderSnapshot, OrderItem.

### Phase 10: Admin Management

**Objective:** Cart items list; prohibit unauthorized changes.

**Actions:**
- Cart Index/Details.
- Product, Design, Color, Category management (preserving clarity).
- Order management, including “can change live? no”.
- User lists with basic starts.

**Dependencies:** Phase 4, Phase 9.

**Acceptance Criteria:**
- Admin completeness + safeguards.

### Phase 11: Security Hardening

**Objective:** Add approvals, oxidation.

**Actions:**
- Approve objects (login_form, roles, homepage).
- Role-based access; enforce.

**Dependencies:** Phase 4.

**Acceptance Criteria:**
- Auth, Role, role-based admin/workflows; no subpage page.

### Phase 12: Testing

**Objective:** Verification.

**Actions:**
- Write tests for CARDS (foundation/security), Approved Approval.
- Tests for Approval flows.

**Dependencies:** Phase 2 (Products/Zinkle), Phase 4.

**Acceptance Criteria:**
- Tests run and pass.

### Phase 13: Performance Optimization

**Objective:** Improvement.

**Actions:**
- Image loading/performance; optimize.
- Profiles, performance stats.
- Checks.

**Dependencies:** Phase 10.

**Acceptance Criteria:**
- Optimization in place; performance stats updated.

### Phase 14: Production Readiness

**Objective:** Market-ready.

**Actions:**
- migrate from sqlite to MySQL/PostgreSQL.
- currency flow, ROI.
- URI + HPC.

**Dependencies:** All previous phases.

**Acceptance Criteria:**
- Production launch checklist + security.

---

## 21. Acceptance Criteria

### 21.1 Bank Card Designer

- Select bank card from landing → designer.
- Choose color → category → design → design color.
- See preview live. Choose backside after front.
- Change color without losing work (state preserved).
- React to incompatibility; report in Persian.
- Position/resize text; bounds respected.
- Add final design to cart with retention message.

### 21.2 Fuel Card Designer

- Choose fuel card → chip size → design → design color → preview → info → backside → cart.

### 21.3 Admin

- Product/Order/User sections accessible and complete.
- Order preview showing customization snapshot.
- Compatibility restriction UI manageable.
- Homepage/CMS content modifiable.
- Security gating (role checks, mask sensitive data).

---

## 22. Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Design compatibility specificity | Medium | High | Define clear admin UI; write compatibility logic in domain layer; test edge cases |
| Order customization state interpretation | Medium | High | Keep CustomizationState and OrderSnapshot separate; snapshot store only IDs and safe blobs; enforce immutability |
| Date/time format localization | High | Medium | Use Intl and Persian locale; store as single format (ISO) and convert at display; document timezone policy |
| File upload size and storage costs | Medium | Medium | Provide clear size limits in UI; use CDN; queue thumbnails; monitor |
| Order/Customization reference schema fragmentation | Medium | Medium | Consolidate IDs and snapshot keys; avoid multiple paths to same data; plan refactor early |

---

## 23. Open Decisions

- **Authentication method**: Email + optional Phone/SMS.
- **Registration**: Email verification mandatory.
- **Password reset**: Email token; optional SMS fallback.
- **Two-factor authentication**: Optional for admin.
- **Role & Permission system**: Spatie Permissions or custom middleware.
- **CMS approach**: Table-based homepage_sections.
- **Export to Admin**: CSV exports via routes; no built-in side menu.
- **Order SMS / Email**: Not specified; deferred to later phases.
- **Product Wordmark approach**: Not part of spec; keep generic.
- **Role field on User (Legacy)**: Not part of production; plan to handle via Roles or user.role if needed.
- **Home Password field (AppServiceProvider via db_reflection)**: Not part of spec; omission kept unless required for authorization.
- **File upload timing**: Not specified; later phases will decide.
- **姓氏性”: Not specified.