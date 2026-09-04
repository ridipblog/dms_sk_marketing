# 1. More polish

# Dealer Invoice Management Database Structure

## Tables Overview

### 1. dealers

Stores dealer master information.

| Column | Type |
| --- | --- |
| id | bigint PK auto increment |
| dealer_name | varchar(255) |
| pan_number | varchar(20) |
| email | varchar(255) |
| phone | varchar(20) |
| total_debit_note_amount | decimal(18,2) nullable |
| total_credit_note_amount | decimal(18,2) nullable |
| status | enum(active, enabled, blocked) |
| created_at | timestamp |
| updated_at | timestamp |

---

### 2. purchase_invoices

Stores dealer purchase invoices.

| Column | Type |
| --- | --- |
| id | bigint PK auto increment |
| invoice_no | varchar unique |
| dealer_id | FK dealers.id |
| total_purchase_amount | decimal(18,2) |
| total_purchase_mt | decimal(18,3) |
| closing_amount | decimal(18,2) |
| remaining_unpaid_quantity | decimal(18,3) |
| purchase_date | date |
| due_date | date |
| status | enum(pending, partially_paid, paid, overdue) |
| created_at | timestamp |
| updated_at | timestamp |

---

### 3. payment_tracks

Stores invoice-wise payment history.

| Column | Type |
| --- | --- |
| id | bigint PK auto increment |
| invoice_id | FK purchase_invoices.id |
| transaction_id | varchar(100) |
| received_amount | decimal(18,2) |
| last_closing_amount | decimal(18,2) |
| payment_for_mt | decimal(18,3) |
| payment_mode | enum(cash, bank_transfer, cheque, upi, adjustment) |
| transaction_date | datetime |
| remarks | text nullable |
| created_at | timestamp |
| updated_at | timestamp |

---

### 4. debit_note_tracks

Stores debit note adjustments.

| Column | Type |
| --- | --- |
| id | bigint PK auto increment |
| payment_track_id | FK payment_tracks.id |
| nos | integer |
| amount | decimal(18,2) |
| reason | varchar(255) nullable |
| created_at | timestamp |
| updated_at | timestamp |

---

### 5. credit_note_tracks

Stores credit note adjustments.

| Column | Type |
| --- | --- |
| id | bigint PK auto increment |
| payment_track_id | FK payment_tracks.id |
| cash_discount_slab_id | FK cash_discount_slabs.id |
| nos | integer |
| amount | decimal(18,2) |
| created_at | timestamp |
| updated_at | timestamp |

---

### 6. cash_discount_slabs

Stores discount slab master data.

| Column | Type |
| --- | --- |
| id | bigint PK auto increment |
| slab_name | varchar(100) |
| minimum_days | integer |
| maximum_days | integer |
| discount_percent | decimal(5,2) |
| status | enum(active, inactive) |
| created_at | timestamp |
| updated_at | timestamp |

---

# Business Logic

## Invoice Closing Amount Formula

closing_amount =

previous_closing_amount

- received_amount
- credit_note_amount
- debit_note_amount

---

# Notes

- `purchase_invoices.closing_amount` stores current live outstanding balance.
- `payment_tracks.last_closing_amount` stores historical balance before payment.
- `payment_tracks.new_closing_amount` intentionally removed to avoid duplicate data.
- Recommended database: PostgreSQL.