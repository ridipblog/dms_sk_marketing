# Product Purchase Module Integration Plan

This document outlines the design and implementation steps for adding a **Product Purchase Module** as a dedicated, standalone module in the Dealer Management System. 

In this design, the company (**JSW/NE INFRA**) purchases products from **external suppliers** to stock their inventory. The module registers suppliers, records purchase invoices, tracks inventory stock increases, and manages accounts payable via its own isolated payment tracking table (`purchase_payment_tracks`).

---

## 1. Core Objectives & Workflow

1. **Dedicated Module Structure**: Keep all supplier and purchase code separate from dealer accounts under a new `Purchase` directory namespace.
2. **Supplier Management**: Add a master list of external suppliers (vendor profiles).
3. **Purchase Invoice Generation**: Record items, quantities, pricing, and GST details for purchases.
4. **Inventory Update**: On purchase finalization, **increment** the company's product stock. Conversely, update the sales flow to **decrement** stock.
5. **Accounts Payable (Financials)**:
   - When a purchase is finalized, record a `PURCHASE` voucher entry in a new **`purchase_payment_tracks`** table.
   - Maintain a running balance of **Total Paid Amount** and **Outstanding Amount** against each invoice in `purchase_invoice_payments`.
   - When a payment is made to the supplier, log a `PAYMENT` voucher in `purchase_payment_tracks` and update the running balance in `purchase_invoice_payments`.

---

## 2. Directory Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Purchase/
│           ├── SupplierController.php         <-- Handles supplier CRUD
│           └── PurchaseInvoiceController.php  <-- Handles purchases, finalization, and supplier payments
├── Models/
│   └── Purchase/
│       ├── Supplier.php
│       ├── PurchaseInvoice.php
│       ├── PurchaseInvoiceDetail.php
│       ├── PurchaseInvoicePayment.php
│       └── PurchasePaymentTrack.php           <-- Isolated payment ledger for purchases
resources/
└── views/
    └── purchase/
        ├── suppliers/
        │   └── index.blade.php                <-- Supplier list/creation UI
        └── invoices/
            ├── index.blade.php                <-- Purchases ledger list
            ├── generate.blade.php             <-- Interactive purchase bill creator
            └── view.blade.php                 <-- Printable invoice detail view
routes/
└── purchase/
    └── purchaseRoute.php                      <-- Scoped purchase and supplier web routes
```

---

## 3. Database Schema Changes

### A. New Tables

#### 1. `suppliers`
Stores details of external vendors.
* `id`: bigint PK auto-increment
* `name`: varchar(255)
* `gstin`: varchar(50) nullable
* `phone`: varchar(20) nullable
* `email`: varchar(255) nullable
* `address`: text nullable
* `status`: tinyint (1 = active, 0 = inactive)
* `created_at` / `updated_at`: timestamps

#### 2. `purchase_invoices`
Stores purchase invoice headers.
* `id`: bigint PK auto-increment
* `invoice_no`: varchar(100) unique (e.g., `PUR-202607-0000001`)
* `supplier_id`: FK referenced to `suppliers.id`
* `company_id`: FK referenced to `companies.id` (JSW or NE INFRA)
* `purchase_date`: date
* `due_date`: date
* `total_quantity`: decimal(15,3) (MT volume)
* `total_amount`: decimal(15,2) (without GST)
* `total_gst_amount`: decimal(15,2)
* `total_cgst_amount`: decimal(15,2)
* `total_sgst_amount`: decimal(15,2)
* `chargeable_amount`: decimal(15,2) (total cost with GST)
* `no_of_goods`: integer (number of distinct items)
* `status`: tinyint (0 = draft, 1 = finalized)
* `created_by`: FK referenced to `user_role_companies.id`
* `created_at` / `updated_at`: timestamps

#### 3. `purchase_invoice_details`
Stores individual line items of a purchase invoice.
* `id`: bigint PK auto-increment
* `purchase_invoice_id`: FK referenced to `purchase_invoices.id` (cascade on delete)
* `product_id`: FK referenced to `products.id` (set null on delete)
* `quantity`: decimal(15,3) (quantity in MT)
* `rate`: decimal(15,2) (purchase price per MT)
* `total_amount`: decimal(15,2) (without GST)
* `cgst_amount` / `sgst_amount` / `gst_amount`: decimal(15,2) (tax breakdowns)
* `chargeable_amount`: decimal(15,2) (total line amount with GST)
* `created_at` / `updated_at`: timestamps

#### 4. `purchase_invoice_payments`
Tracks the running outstanding balance for each purchase invoice.
* `id`: bigint PK auto-increment
* `purchase_invoice_id`: FK referenced to `purchase_invoices.id` (cascade on delete)
* `outstanding_amount`: decimal(15,2) (starts at invoice total, decreases as payments are made)
* `paid_amount`: decimal(15,2) (starts at 0, increases as payments are made)
* `clear_status`: tinyint (0 = unpaid/partial, 1 = fully paid)
* `created_at` / `updated_at`: timestamps

#### 5. `purchase_payment_tracks`
Isolated ledger for recording transactions against purchase invoices (analogous to `payment_tracks` for dealers).
* `id`: bigint PK auto-increment
* `purchase_invoice_id`: FK referenced to `purchase_invoices.id` (cascade on delete)
* `transaction_id`: varchar(100) nullable
* `amount`: decimal(18,2)
* `balance_amount`: decimal(18,2) nullable
* `payment_mode`: enum('cash', 'bank_transfer', 'cheque', 'upi', 'adjustment', 'entry')
* `voucher_type`: enum('PURCHASE', 'PAYMENT')
* `transaction_date`: datetime
* `remarks`: text nullable
* `created_by`: FK referenced to `user_role_companies.id` (nullable)
* `created_at` / `updated_at`: timestamps

### B. Table Modifications

#### 1. `products` (Inventory Stock)
Add stock tracking column:
* `stock_quantity`: decimal(15,3) (default `0.000`)

---

## 4. Models & Relationships

- **`App\Models\Purchase\Supplier`**: `hasMany(PurchaseInvoice::class)`
- **`App\Models\Purchase\PurchaseInvoice`**: 
  - `belongsTo(Supplier::class)`
  - `belongsTo(Company::class)`
  - `hasMany(PurchaseInvoiceDetail::class)`
  - `hasOne(PurchaseInvoicePayment::class)`
  - `hasMany(PurchasePaymentTrack::class)`
  - `createdBy()` (belongsTo `UserRoleCompany`)
- **`App\Models\Purchase\PurchaseInvoiceDetail`**: `belongsTo(PurchaseInvoice::class)`, `belongsTo(Product::class)`
- **`App\Models\Purchase\PurchaseInvoicePayment`**: `belongsTo(PurchaseInvoice::class)`
- **`App\Models\Purchase\PurchasePaymentTrack`**: `belongsTo(PurchaseInvoice::class)`
- **`App\Models\Inventory\Product`**: Add `stock_quantity` to fillable & casts.

---

## 5. Financial & Inventory Business Rules

### When a Purchase Invoice is Finalized:
1. Change invoice status to **Finalized**.
2. **Increase** the corresponding product stock:
   `product.stock_quantity = product.stock_quantity + purchase_detail.quantity`
3. Create a financial voucher record of type `PURCHASE` in `purchase_payment_tracks`:
   - `amount` = `chargeable_amount`
   - `balance_amount` = `chargeable_amount`
   - `payment_mode` = `'entry'`
   - `voucher_type` = `'PURCHASE'`
4. Initialize the invoice payment totals in `purchase_invoice_payments`:
   - `outstanding_amount` = `chargeable_amount`
   - `paid_amount` = `0.00`
   - `clear_status` = `0`

### When a Payment is Sent to the Supplier:
1. Create a voucher of type `PAYMENT` in `purchase_payment_tracks` referencing the `purchase_invoice_id`:
   - `amount` = `payment_amount`
   - `balance_amount` = `previous_outstanding_amount - payment_amount`
   - `payment_mode` = selectable by user (UPI, Bank Transfer, Cash, etc.)
   - `voucher_type` = `'PAYMENT'`
2. Update the `purchase_invoice_payments` record:
   - Increment `paid_amount` (`paid_amount = paid_amount + payment_amount`)
   - Decrement `outstanding_amount` (`outstanding_amount = outstanding_amount - payment_amount`)
   - If `outstanding_amount <= 0`, set `clear_status = 1`.

### When a Sales Invoice is Finalized:
* **Decrease** the corresponding product stock:
  `product.stock_quantity = product.stock_quantity - sales_detail.quantity`

---

## 6. Web Routes (`routes/purchase/purchaseRoute.php`)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Purchase\SupplierController;
use App\Http\Controllers\Purchase\PurchaseInvoiceController;
use App\Http\Controllers\Inventory\StockController;

Route::middleware(['auth'])->prefix('purchase')->group(function () {
    // Suppliers CRUD
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('purchase.suppliers.index');
    Route::post('/suppliers/list', [SupplierController::class, 'list'])->name('purchase.suppliers.list');
    Route::post('/suppliers/store', [SupplierController::class, 'store'])->name('purchase.suppliers.store');

    // Purchase Invoices
    Route::get('/invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase.invoices.index');
    Route::get('/invoices/generate/{purchase_id?}', [PurchaseInvoiceController::class, 'generate'])->name('purchase.invoices.generate');
    Route::get('/invoices/view/{id}', [PurchaseInvoiceController::class, 'view'])->name('purchase.invoices.view');

    // Purchase APIs
    Route::post('/invoices/list', [PurchaseInvoiceController::class, 'list'])->name('purchase.invoices.list');
    Route::post('/invoices/store', [PurchaseInvoiceController::class, 'store'])->name('purchase.invoices.store');
    Route::post('/invoices/fetch-items', [PurchaseInvoiceController::class, 'fetchPurchaseItems'])->name('purchase.invoices.fetch_items');
    Route::post('/invoices/store-item', [PurchaseInvoiceController::class, 'storePurchaseItem'])->name('purchase.invoices.store_item');
    Route::post('/invoices/delete-item', [PurchaseInvoiceController::class, 'deletePurchaseItem'])->name('purchase.invoices.delete_item');
    Route::post('/invoices/finalize', [PurchaseInvoiceController::class, 'finalizePurchase'])->name('purchase.invoices.finalize');
    Route::post('/invoices/payment', [PurchaseInvoiceController::class, 'storePayment'])->name('purchase.invoices.payment');
});

Route::middleware(['auth'])->group(function () {
    // Inventory Stocks Console
    Route::get('/stocks', [StockController::class, 'index'])->name('inventory.stocks.index');
    Route::post('/stocks/adjust', [StockController::class, 'adjust'])->name('inventory.stocks.adjust');
});
```

To enable these routes, we will append `require __DIR__.'/purchase/purchaseRoute.php';` to `routes/web.php`.

---

## 7. Sidebar Configuration

We will add a new **Purchase Management** section in `config/sidebar.php`:

```php
        [
            'title' => 'Purchase Management',
            'icon' => 'fas fa-shopping-cart',
            'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
            'submodules' => [
                [
                    'title' => 'Suppliers',
                    'route' => 'purchase.suppliers.index',
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Purchase Invoices',
                    'route' => 'purchase.invoices.index',
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ],
                [
                    'title' => 'Product Stock',
                    'route' => 'inventory.stocks.index',
                    'roles' => ['Admin Users', 'Super Admin', 'Manager', 'Assistant Section Officer (ASO)', 'Finance Team'],
                ]
            ]
        ],
```

---

## 8. View Interfaces (Premium Styling)

1. **Suppliers List**: Integrated datatable UI to register, search, and view suppliers.
2. **Purchase Invoice List**: Scopes to selected supplier, shows total amount, total paid, outstanding balance, and billing dates.
3. **Interactive Invoice Builder**: Real-time line calculations for product quantities, custom purchase rates, and GST math.
4. **Stock Ledger Console**: Clean overview showing current product stock balances with filters by category and a stock correction modal.
