# Standalone Product Purchase Module Integration Plan

This plan details the implementation of a **Product Purchase Module** designed as a dedicated, standalone module separate from the Dealer Accounts module. 

The company (**JSW/NE INFRA**) purchases products from **external suppliers** to stock their inventory. Making the Purchase module separate prevents mixing supplier workflows with dealer accounting logic, making roles, permissions, code structures, and views much cleaner and simpler to maintain.

---

## User Review Required

> [!IMPORTANT]
> - **Isolated Structure**: All files related to purchases and suppliers will be located in their own directories:
>   - Controllers: `App\Http\Controllers\Purchase\`
>   - Models: `App\Models\Purchase\`
>   - Views: `resources/views/purchase\`
>   - Routes: `routes/purchase\purchaseRoute.php`
> - **Supplier Master Table**: We will introduce a new `suppliers` table to store external vendors.
> - **Company Stock Addition**: When a Purchase Invoice is finalized, it will **increase** the company's available stock of products in the database.
> - **Financial Ledger & Voucher Integration**:
>   - We will create an isolated **`purchase_payment_tracks`** table to record transaction history (purchase bill and payment vouchers) specifically for external purchases.
>   - A dedicated `purchase_invoice_payments` table tracks the live running totals: **Total Paid Amount** and **Outstanding Amount** for each purchase invoice.

---

## Open Questions

- What fields are required for the `suppliers` master table? We propose: `name`, `gstin`, `phone`, `email`, `address`, and `status`.
- Do we need an approval workflow for purchases (e.g., Draft -> Ordered -> Received -> Invoiced)? We propose starting with a standard **Draft -> Finalized** invoice flow.

---

## Proposed Changes

### Database Layer

#### [NEW] [2026_07_07_000001_create_suppliers_table.php](file:///d:/dealer_management_system/delaler_management_system_jsw/database/migrations/2026_07_07_000001_create_suppliers_table.php)
- Creates the `suppliers` table:
  - `id` (bigint PK)
  - `name` (varchar)
  - `gstin` (varchar, nullable)
  - `phone` (varchar, nullable)
  - `email` (varchar, nullable)
  - `address` (text, nullable)
  - `status` (tinyint: `1` = active, `0` = inactive)
  - `timestamps`

#### [NEW] [2026_07_07_000002_create_purchase_invoices_table.php](file:///d:/dealer_management_system/delaler_management_system_jsw/database/migrations/2026_07_07_000002_create_purchase_invoices_table.php)
- Creates the `purchase_invoices` table:
  - `id` (bigint PK)
  - `invoice_no` (varchar, unique, e.g. `PUR-202607-0000001`)
  - `supplier_id` (FK to `suppliers`)
  - `company_id` (FK to `companies`, scopes the purchase to JSW or NE INFRA)
  - `purchase_date` (date)
  - `due_date` (date)
  - `total_quantity` (decimal 15,3, in MT)
  - `total_amount` (decimal 15,2, without GST)
  - `total_gst_amount` (decimal 15,2)
  - `total_cgst_amount` (decimal 15,2)
  - `total_sgst_amount` (decimal 15,2)
  - `chargeable_amount` (decimal 15,2, with GST)
  - `no_of_goods` (integer)
  - `status` (tinyint: `0` = draft/pending, `1` = finalized)
  - `created_by` (FK to `user_role_companies`)
  - `timestamps`

#### [NEW] [2026_07_07_000003_create_purchase_invoice_details_table.php](file:///d:/dealer_management_system/delaler_management_system_jsw/database/migrations/2026_07_07_000003_create_purchase_invoice_details_table.php)
- Creates the `purchase_invoice_details` table:
  - `id` (bigint PK)
  - `purchase_invoice_id` (FK to `purchase_invoices`, cascade on delete)
  - `product_id` (FK to `products`, set null on delete)
  - `quantity` (decimal 15,3, in MT)
  - `rate` (decimal 15,2, cost per MT)
  - `total_amount` (decimal 15,2, without GST)
  - `cgst_amount` (decimal 15,2)
  - `sgst_amount` (decimal 15,2)
  - `gst_amount` (decimal 15,2)
  - `chargeable_amount` (decimal 15,2, with GST)
  - `timestamps`

#### [NEW] [2026_07_07_000004_create_purchase_invoice_payments_table.php](file:///d:/dealer_management_system/delaler_management_system_jsw/database/migrations/2026_07_07_000004_create_purchase_invoice_payments_table.php)
- Creates the `purchase_invoice_payments` table for matching outstanding balance:
  - `id` (bigint PK)
  - `purchase_invoice_id` (FK to `purchase_invoices`, cascade on delete)
  - `outstanding_amount` (decimal 15,2)
  - `paid_amount` (decimal 15,2, default 0)
  - `clear_status` (tinyint, default 0)
  - `timestamps`

#### [NEW] [2026_07_07_000005_create_purchase_payment_tracks_table.php](file:///d:/dealer_management_system/delaler_management_system_jsw/database/migrations/2026_07_07_000005_create_purchase_payment_tracks_table.php)
- Creates the isolated `purchase_payment_tracks` table for recording transaction history specifically for external purchases:
  - `id` (bigint PK)
  - `purchase_invoice_id` (FK referenced to `purchase_invoices.id`, cascade on delete)
  - `transaction_id` (varchar, nullable)
  - `amount` (decimal 18,2)
  - `balance_amount` (decimal 18,2, nullable)
  - `payment_mode` (enum: cash, bank_transfer, cheque, upi, adjustment, entry)
  - `voucher_type` (enum: PURCHASE, PAYMENT)
  - `transaction_date` (datetime)
  - `remarks` (text, nullable)
  - `created_by` (FK referenced to `user_role_companies.id`, nullable)
  - `timestamps`

#### [NEW] [2026_07_07_000006_add_stock_to_products_table.php](file:///d:/dealer_management_system/delaler_management_system_jsw/database/migrations/2026_07_07_000006_add_stock_to_products_table.php)
- Adds `stock_quantity` (decimal 15,3, default `0.000`) to the `products` table.

---

### Models & Business Logic Layer

#### [MODIFY] [Product.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Models/Inventory/Product.php)
- Append `stock_quantity` to the fillable array and cast it as `decimal:3`.

#### [NEW] [Supplier.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Models/Purchase/Supplier.php)
- Define `Supplier` Eloquent Model inside `App\Models\Purchase` with relationships:
  - `purchaseInvoices()` (hasMany `PurchaseInvoice`)

#### [NEW] [PurchaseInvoice.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Models/Purchase/PurchaseInvoice.php)
- Define `PurchaseInvoice` Eloquent Model inside `App\Models\Purchase` with relationships:
  - `supplier()` (belongsTo `Supplier`)
  - `company()` (belongsTo `Company`)
  - `purchaseInvoiceDetails()` (hasMany `PurchaseInvoiceDetail`)
  - `purchaseInvoicePayment()` (hasOne `PurchaseInvoicePayment`)
  - `purchasePaymentTracks()` (hasMany `PurchasePaymentTrack`)
  - `createdBy()` (belongsTo `UserRoleCompany`)

#### [NEW] [PurchaseInvoiceDetail.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Models/Purchase/PurchaseInvoiceDetail.php)
- Define `PurchaseInvoiceDetail` Eloquent Model inside `App\Models\Purchase` with relationships:
  - `purchaseInvoice()` (belongsTo `PurchaseInvoice`)
  - `product()` (belongsTo `Product`)

#### [NEW] [PurchaseInvoicePayment.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Models/Purchase/PurchaseInvoicePayment.php)
- Define `PurchaseInvoicePayment` Eloquent Model inside `App\Models\Purchase` for tracking outstanding balance.

#### [NEW] [PurchasePaymentTrack.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Models/Purchase/PurchasePaymentTrack.php)
- Define `PurchasePaymentTrack` Eloquent Model inside `App\Models\Purchase` representing transaction history.

#### [MODIFY] [ReuseModule.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Modules/ReuseModule.php)
- Add helper `generatePurchaseInvoiceNumber()` using atomic database lock to safely issue unique purchase serials (`PUR-YYYYMM-0000001`).

---

### Controllers & HTTP Routing

#### [NEW] [SupplierController.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Http/Controllers/Purchase/SupplierController.php)
- Implements CRUD operations for Suppliers under `App\Http\Controllers\Purchase` namespace.

#### [NEW] [PurchaseInvoiceController.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Http/Controllers/Purchase/PurchaseInvoiceController.php)
- Implements:
  - `index()`: Lists and filters company purchases.
  - `list(Request $request)`: AJAX lists/paginates company purchase invoices.
  - `generate($purchase_id = null)`: Serves purchase invoice draft layout.
  - `store(Request $request)`: Creates a draft purchase invoice.
  - `fetchPurchaseItems(Request $request)`: Scopes and returns draft detail rows.
  - `storePurchaseItem(Request $request)`: Saves/updates a purchase invoice line item, computing taxes.
  - `deletePurchaseItem(Request $request)`: Deletes an item from the draft.
  - `finalizePurchase(Request $request)`: finalizes purchase invoice, updates product stock levels (`stock_quantity`), creates `PURCHASE` voucher entry in `purchase_payment_tracks`, and creates initial `purchase_invoice_payments` tracking.
  - `view($id)`: Displays printable invoice details.
  - `storePayment(Request $request)`: Records a payment sent to the supplier, updating `purchase_invoice_payments` outstanding balances and adding a `PAYMENT` voucher entry in `purchase_payment_tracks`.

#### [NEW] [StockController.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Http/Controllers/Inventory/StockController.php)
- Implements index view listing all product stock levels and manual stock adjustments.

#### [MODIFY] [InvoiceController.php](file:///d:/dealer_management_system/delaler_management_system_jsw/app/Http/Controllers/Accounts/InvoiceController.php)
- Modify `finalizeInvoice` to **decrease** product stock quantity when a sales invoice is generated.

#### [NEW] [purchaseRoute.php](file:///d:/dealer_management_system/delaler_management_system_jsw/routes/purchase/purchaseRoute.php)
- Append purchase routes:
  - GET `/purchase/suppliers` -> `SupplierController@index`
  - POST `/purchase/suppliers/list` -> `SupplierController@list`
  - POST `/purchase/suppliers/store` -> `SupplierController@store`
  - GET `/purchase/invoices` -> `PurchaseInvoiceController@index`
  - GET `/purchase/invoices/generate/{purchase_id?}` -> `PurchaseInvoiceController@generate`
  - GET `/purchase/invoices/view/{id}` -> `PurchaseInvoiceController@view`
  - POST `/purchase/invoices/list` -> `PurchaseInvoiceController@list`
  - POST `/purchase/invoices/store` -> `PurchaseInvoiceController@store`
  - POST `/purchase/invoices/fetch-items` -> `PurchaseInvoiceController@fetchPurchaseItems`
  - POST `/purchase/invoices/store-item` -> `PurchaseInvoiceController@storePurchaseItem`
  - POST `/purchase/invoices/delete-item` -> `PurchaseInvoiceController@deletePurchaseItem`
  - POST `/purchase/invoices/finalize` -> `PurchaseInvoiceController@finalizePurchase`
  - POST `/purchase/invoices/payment` -> `PurchaseInvoiceController@storePayment`

#### [MODIFY] [inventoryRoutes.php](file:///d:/dealer_management_system/delaler_management_system_jsw/routes/inventory/inventoryRoutes.php)
- Append stock routes:
  - GET `/stocks` -> `StockController@index`
  - POST `/stocks/adjust` -> `StockController@adjust`

#### [MODIFY] [web.php](file:///d:/dealer_management_system/delaler_management_system_jsw/routes/web.php)
- Add: `require __DIR__.'/purchase/purchaseRoute.php';`

---

### Sidebar Configuration

#### [MODIFY] [sidebar.php](file:///d:/dealer_management_system/delaler_management_system_jsw/config/sidebar.php)
- Add a new "Purchase Management" main section to the sidebar config with submenu entries for **Suppliers**, **Purchase Invoices**, and **Product Stock**.

---

### View Templates

#### [NEW] Suppliers Views (`resources/views/purchase/suppliers/`)
- Renders the interactive datatable of all suppliers with add/edit functionality.

#### [NEW] Purchase Invoices Views (`resources/views/purchase/invoices/`)
- Interactive purchase ledger dashboard, draft itemization canvas, and printable purchase receipt summaries.

#### [NEW] Stock Views (`resources/views/inventory/stock/`)
- Clean layout displaying product-wise stock, search, and manual adjustments modal.

---

## Verification Plan

### Automated Tests
- Implement new feature tests in `tests/Feature/CompanyPurchaseTest.php`:
  - Test registering a supplier.
  - Test creating a purchase invoice draft.
  - Test adding items and verifying total cost + taxes.
  - Test finalizing a purchase invoice correctly **increases** the product's `stock_quantity`.
  - Test finalizing a sales invoice correctly **decreases** the product's `stock_quantity`.
  - Run `php artisan test --filter=CompanyPurchaseTest`.

### Manual Verification
1. Log in as an Admin, click "Suppliers" in the sidebar and register a new supplier.
2. Navigate to "Purchase Invoices", choose the registered supplier, and create a purchase invoice draft.
3. Add products and quantities, verify rates and taxes are computed correctly.
4. Finalize the invoice.
5. Go to the "Product Stock" page and verify the product stock has increased.
6. Record a partial or full payment on the purchase invoice view and verify the balance update.
7. Create a dealer sales invoice with the same products, finalize it, and verify that the product stock decreases.
