# ARCHITECTURE.md

## High-Level Overview

This document summarizes the technical architecture of the system.

### Stack (Locked)
- Backend: Laravel 13, PHP 8.3
- Frontend: Livewire 4, Vite 8, Tailwind CSS 4 (RTL)
- Database: SQLite (development), MySQL/PostgreSQL (production)
- Authentication: Laravel native

### Application Layers

1. **Presentation Layer**
   - Livewire components (bank/fuel card designers, admin forms)
   - Blade templates (layouts, partials)
   - JavaScript/Vite (lazy load, interaction, form hints)

2. **Application Layer**
   - Services: OrderSubmissionService, CartService, DesignCompatibilityService, PricingService, CategoryService
   - Form request classes (user, order, product validation)
   - Events/Subscribers (checkout success, email notifications)
   - Media (image upload and processing)

3. **Domain Layer**
   - Eloquent models: Product, CustomizationState, OrderSnapshot, etc.
   - Domain events
   - Business rules (compatibility checks, pricing rules)
   - Service/Repository interfaces (future expansion)

4. **Data Access Layer**
   - Laravel Eloquent ORM
   - Query builder
   - Transaction management
   - Caching (Redis - optional, later)

5. **Infrastructure**
   - Database (MySQL/PostgreSQL)
   - FAL (filesystem), CDN
   - Email/Notification system
   - Logging
   - Queue system (background jobs for e.g., email, export)

---

## Domain Model Relationships

### User-Order Flow

```
User (1)
  └── hasMany: Order (n)
         └── hasMany: OrderItem (m)
                └── belongsTo: CartItem (optional, if cart persists)
                └── hasOne: OrderSnapshot (1)
                       └── selected_product (Foreign)
                       └── selected_card_color_id (FK nullable)
                       └── selected_design_id (FK nullable)
```

### Product Variant Hierarchy (refactored from CardType)

```
Product (abstract base)
  ├── type: 'bank' → BankProduct
  ├── type: 'fuel' → FuelProduct
  └── type: 'standard' → StandardProduct
        └── type: 'serial' → SerialProduct (future)
```

BankProduct includes:
- card_color_id (FK to Color)
- base_price, base_shipping
- chipless (boolean)
- supports_colored_instructions (boolean)
- MetalChip (1-to-1, nil or none)

FuelProduct includes:
- chip_type (enum: 'none' | 'small' | 'large')
- MetalChip (1-to-1, nil or single record)

StandardProduct includes:
- engraving_config (json)

### Design + Color Compatibility Hierarchy (from legacy models)

```
Color (1)
  ├── hasMany: ColorRestrictions (for design color vs card color incompatibility)
  └── hasMany: BankProduct (FK)

DesignImage (1)
  ├── belongsTo: Design
  ├── belongsTo: Color (its design color variant)
  ├── hasMany: DesignColorRestrictions
  └── hasMany: CustomizationState (indirect)

DesignColorRestriction (N:M pivot)
  ├── design_image_id
  └── forbidden_card_color_id
```

### Cart Flow

Anonymous: Laravel session (simple JSON) initialized by session_id.

Authenticated: Database-cart via cart + cart_items.

```
Cart (optional, for DB-based)
  └── hasMany: CartItem
         └── product_id
         └── quantity
         └── customization_snapshot (schema: only IDs and non-personal data)
```

Expiration:
- 24h window based on created_at on CartItem or Cart.
- On checkout: clear or mark completed.

---

## Livewire Components

### Designer Components

1. **BankCardDesigner**
   - Purpose: Interactive single-page designer for bank cards.
   - Props: step, selectedCardTypeId, selectedColorId, selectedCateDesignId, selectedGroupDesignId, selectedDesignId, selectedDesignImageId, card info fields (holderName, cardNumber, cvv2, expiryDate), fieldPositions (json), saved flag.
   - Responsibilities: Step management, compatibility checks, form validation, cart integration (final step), live preview synchronization (linked to result column).
   - Computed properties: availableColors, cateDesigns, groupDesigns, designs, designImages, selectedColor, selectedCardType, selectedDesignImage, isLightColor.

2. **FuelCardDesigner**
   - Purpose: Interactive single-page designer for fuel cards.
   - Props: step, chipSize, selectedCardTypeId, selectedDesignId, selectedDesignImageId, search, blackCardColorId, backside info fields (ownerName, carModel, vinNumber, sysNumber, plateNumber), saved flag.
   - Responsibilities: Step management, chip size selection, category-sorting preferences, compatibility enforcement, cart integration, live preview synchronization.
   - Computed properties: designs, designImages, selectedCardType, selectedDesignImage.

### Admin Components

1. **Dashboard**
   - Purpose: High-level overview for admins.
   - Props: optional.
   - Computed properties: totalUsers, totalOrders, pendingOrders, totalCardTypes (or actual product count), etc.

2. **ColorManager, CateDesignManager, CardTypeManager (legacy names)**
   - Purpose: CRUD for base entities.
   - TODO: Refactor under Products and Denoted Names.

3. **OrderManager**
   - Purpose: Complete orders CRUD with snapshot previews.
   - Props: may use pagination for list.
   - Computed properties: High-level aggregate metrics.
   - Features: Preview customization snapshot, export csv (via route), change status, record share details.

4. **UserManager**
   - Purpose: Basic user list.
   - TODO: Add addresses, insight includes orders.

5. **ProductManager**
   - Purpose: Product variant CRUD and listing.

---

## Database Schema (Reference)

### Tables (Core)

- users
  - id, name, phone, address nullable, password, remember_token
  - email nullable (for ground truth)

- products + variants (bank_products, fuel_products, standard_products)
  - id (FK to products)
  - type specialization fields (color_id, chip_type, etc.)

- metal_chips
  - id, product_id, chip_type

- colors, designs, design_images, design_category_naming
- homepage_sections (CMS)
- user_address_primary
- https_secret_key (for approvals)
- cart, cart_items
- orders + order_items
- order_snapshots

### Models and Relationships (key)

- Product
  - morphedByMany to BankProduct through type, etc.
  - (future) attaches: StandardProduct, SerialProduct

- BankProduct
  - belongsTo: Color
  - hasOne: MetalChip (in optional)

- FuelProduct
  - hasOne: MetalChip (in optional)

- Color
  - hasMany: BankProduct
  - hasMany: DesignImage (as design color)
  - hasMany: DesignColorRestriction (as card color)

- DesignImage
  - belongsTo: Design
  - belongsTo: Color (design color variant)
  - hasMany: CustomizationState (if current)
  - hasMany: DesignColorRestriction

- DesignColorRestriction
  - belongsTo: DesignImage
  - belongsTo: Color (forbidden card color)

- CustomizationState
  - morphs('customizable_id', 'customizable_type')
  - belongsTo: BankProduct (or FK: selected_product_id)
  - belongsTo: DesignImage (nullable)
  - selected_design_image_id (nullable)
  - text_elements (nullable for now)

- Order
  - belongsTo: User
  - hasMany: OrderItem

- OrderItem
  - belongsTo: Order
  - belongsTo: BankProduct
  - belongsTo: CustomizationState
  - (future) belongsTo: CartItem

- OrderSnapshot
  - belongsTo: Order
  - selected_product_id
  - selected_card_color_id (nullable)
  - selected_design_id (nullable)
  - selected_design_image_id (nullable)
  - text_elements_snapshot (nullable)
  - customization_state_checksum (nullable)

---

## Data Flows

### User Creates Bank Card Order

1. Customer lands on Home → selects Bank Card product.
2. Steps clicked into BankCardDesigner.
   - Designer synchronizes product specifications.
   - State is captured per step (proha is not persisted until checkout).
   - Final state: Designer reads product field positions and renders live; background is planned.
3. After finalizing customization, Designer calls CartService.addItem:
   - Object: Product (BankProduct), price (calculated), quantity, plus only IDs for customization (no PDUs).
   - Cart storage: session (simple) or DB (if connected); 24h retention based on creation_at.
4. Customer adds to Cart → retains window message displayed before checkout.
5. From Cart, user proceeds to Checkout. On Checkout success:
   - CartService.removeItem (all items) → marks plaintext—no changes—if they’ve already been used.
   - OrderSubmissionService creates Order and OrderSnapshot:
     - Retrieves product/type/design IDs.
     - Persists the state snapshot (selected_product_id, selected_card_color_id, selected_design_id, selected_design_image_id, text_elements_snapshot, customization_state_checksum).
     - Notably, Text elements are JSON fields, none of which contain PDUs.
   - Central notice: Invalid snapshot (no changes) is reported to admin; frontend resets tracking.

### Admin Order Management

1. Admin navigates to OrderManager drview (via admin route).
2. OrderManager lists orders (with pagination) and permits searching/filtering.
3. On click, OrderManager opens order details view.

4. Details view shows:
   - Basic info: order_ref, total price, status, created_at.
   - Customer: name, phone.
   - Items: linked via Product and Snapshot for a definitive list; product IDs supply context.
   - Customization: rendered preview via OrderSnapshot fields+product design references (ensures a fair snapshot).
5. Actions: Add notes, set status, export to CSV.
6. Security: Enforce has_access; mask card_number values.

### Compatibility Check

1. User selects a bank card color.
2. BankCardDesigner calls DesignCompatibilityService.hasIncompatibility(product_id, card_color_id, design_image_id).
3. Service checks DesignImage.HasColorRestrictions where forbidden_card_color_id matches card_color_id.
4. If true → forbid selection in frontend and show a warning in Persian.
5. On checkout → capture only compatible choices in the snapshot.

### State Preservation

1. When user changes card color in BankCardDesigner:
   - Existing states (layout, font size, text fields) are preserved.
   - The next step in the flow triggered only if valid; otherwise incompatibility feedback clears selection and shows warning (no destructive change without acknowledgment).

---

## Customization Flow (Bank Card)

1. Choose Product (Bank) → used from Products table.
2. Choose Card Color → stored as selected_card_color_id.
3. Choose Category → filtered by CateDesign.
4. Choose Design → from selected GroupDesign's designs.
5. Choose Design Color → from design images of the selected design, filtered by compatibility with the bank card color.
6. Preview updates live (no persistence at this stage; object is not persisted until finalize).
7. Proceed to Backside:
   - Inputs for holder_name, card_number, optional cvv2, expiryDate.
   - Text positioning tags (field_positions).
8. Save Button → calls CartService.addItem:
   - Product selection; design/color selections; text elements (field_positions); no PDUs.
   - Cart stores a snapshot of non-pii fields.
9. Cart persists until checkout; retention might require session/database approach or integrated with OrderSequence.

---

## Order Snapshot Flow

1. CartService.processCheckout() validates items exist.
2. OrderSubmissionService creates Order record in orders DB:
   - order_id, user_id, total_price, status.

3. For each CartItem:
   - OrderSubmissionService creates OrderItem:
     - order_id, product_id, quantity, price
     - CustomizationState is copied from item.customization_snapshot.
   - OrderSubmissionService creates OrderSnapshot:
     - order_id, product_id (from item)
     - selected_card_color_id (from customization_snapshot)
     - selected_design_image_id (from customization_snapshot, nullable)
     - text_elements_snapshot (from customization_snapshot, nullable)
     - customization_state_checksum (hash of customizations JSON)
   - Logs snapshot creation with index keys.

4. CartService.clear() (all used items removed; otherwise left as legacy links).

5. Two-date observation: Admin orders are later read via Order->orderItems->customizations+snapshots to reconstruct view without accessing current Product/Color/Design objects.

---

## Admin Flow

1. Admin logs into AdminPanel (role verification).
2. Dashboard shows high-level metrics (User count, Order count, Snapshot position, etc.).
3. Product Management:
   - Inventory/Status for Products, Colors, Designs.
   - Product listing corrects designability (no pending changes in spec).
4. Design Management:
   - Compatibility UI (which design variants are blocked per card color).
5. Order Management:
   - Full order list with snapshot info.
   - Order types, design colors selection.
6. User Management:
   - Basic user stats and filter (linked to order records).

---

## Security Considerations

- CSRF: Standard middleware in Laravel for each request.
- Authorization: Limits on Admin access per order; auth and role checks on all sensitive forms.
- Authorization Inside Livewire: Gate checks for admin-only actions; no reliance on frontend toggles.
- SQL Injection: Eloquent and Query Builder; no raw SQL used.
- XSS: Trimmed user input; defensive escaping for user-supplied values.
- Sensitive Data:
  - card_number masked in admin.
  - cvv2 never stored.
  - Personal fields (phone, email, address) trackable; logging excludes PDUI.
- File Uploads: Validate types/size; store via Storage (FAL) and CDN; no renames of originals (option future).

---

## Performance Considerations

### Live Preview

- Serve optimized images (WebP), lazy load, CDN.
- Avoid full re-processing, fine-tune query sizes in Designer.

### Cart & Orders

- Database indexes for orders, product, color, status where appropriate.
- Use recall caching for common queries (products list, design combinatorics) if needed.
- Queue background notifications (mail) for checkout.

### Reports & Exports

- Use queued tasks for export processing (csv) rather than blocking requests.

---

## Deployment Architecture (Strategic)

1. MySQL/PostgreSQL database (production).
2. Queue workers for background tasks (email, notifications, future royalties).
3. CDN for static assets (images, CSS, JS).
4. Load balancer (optional for scaling).
5. Separate environments: dev, staging, production, with distinct configs and roles.

---

## Future Expansion

- Separate project for royalty estimation.
- Role-based admin customization (Spatie Permission model, or keep custom middleware).
- Advanced analytics (sales metrics, retention).
- Multi-currency, multi-language (beyond basic RTL to LTR for partners).
- Payment gateway integration (given specs explicitly not to implement in this phase).