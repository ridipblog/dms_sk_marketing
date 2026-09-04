# User Acceptance Testing (UAT) Process Flow Guide
## Dealer Management & Finance Automation System (JSW / Eskay Marketing)

This document provides a step-by-step testing blueprint for verifying the system's core workflows during User Acceptance Testing (UAT). It aligns with the actual implementation and database models configured in the project workspace.

---

## Document Map
1. **Flow 1: Authentication & Context Selection (Multi-Role / Multi-Company)**
2. **Flow 2: Dynamic Role-Based Dashboards**
3. **Flow 3: Dealer Management Module (CRUD & Dynamic Balances)**
4. **Flow 4: Inventory Management Module (Categories & Products)**
5. **Flow 5: Dealer Account & Finance Module (Invoices, Payments, Ledger & Auto-Calculations)**
6. **Flow 6: Supplier & Purchase Management Module (Suppliers, Purchase Invoices & Stocks)**
7. **Flow 7: Settings Module (Change Password)**
8. **Flow 8: User Management Module (CRUD, Allocations & Excel Upload)**

---

## 1. Authentication & Context Selection

### Purpose
To verify that users can successfully authenticate and, if mapped to multiple subsidiaries/roles (e.g., JSW, Eskay Marketing as Branch Manager, ASM, ASO, or Finance), select their active working context.

### Authentication Process Flow Table

| Step | Action to Perform | Expected System Response |
| :---: | :--- | :--- |
| **1** | Open browser and visit `/login`. | Login page renders correctly. |
| **2** | Enter test Phone Number and Password, click **Login**. | Credentials verified. System checks assigned company mappings. |
| **3** | *If User has 1 mapping:* Auto-initializes context. | Redirects straight to the **Home Dashboard**. |
| **4** | *If User has multiple mappings:* Launches selection modal. | Page locks and displays the **Select Role & Company** modal. |
| **5** | Select context from the dropdown list and click **Continue**. | Session active context variables set, redirects to dashboard. |
| **6** | Click **Switch Context** in the top navbar at any time. | Re-opens selection modal for switching contexts. |

### UAT Test Steps

#### Step 1: Login Submission
1. Navigate to the Login page (`/login`).
2. Input a test **Phone Number** (10-15 digits) and **Password**.
3. *Optional:* Check **Remember Me**.
4. Click **Login**.

#### Step 2: Context Selection (If Multi-Role/Company)
*   **Scenario A: User has only ONE assigned Role & Company mapping:**
    *   **Expected Result:** The system automatically initializes the session, sets active IDs (company, role, etc.), and redirects you directly to the Dashboard.
*   **Scenario B: User has MULTIPLE assigned Role & Company mappings:**
    *   **Expected Result:** A modal window titled **"Select Role & Company"** pops up. Page interaction is locked (`data-bs-backdrop="static"`).
    *   **Action:** Select the desired context from the dropdown (format: `Company Name (Role: Role Name)`) and click **Continue**.
    *   **Expected Result:** The system configures the active context in the session and redirects you to the Dashboard.

#### Step 3: Swapping Context Post-Login
1. Locate the context display (e.g., Company/Role indicators) on the top navbar.
2. Click on **Switch Context**.
3. Select a different role/company combination and click **Continue**.
4. **Expected Result:** The page refreshes, and the UI displays data filtered by the newly selected company and role context.

---

## 2. Dynamic Role-Based Dashboards

### Purpose
To verify that the dashboard loads the correct metrics and charts customized for the authenticated user's active role.

| Active Role | Expected Metrics & UI Elements to Verify |
| :--- | :--- |
| **Branch Manager (BM)** / **Finance** | Executive overview of total sales (achievement), outstanding dues, received collections, active ASM and ASO counters, regional sales lists, and top performing sales personnel. |
| **Area Sales Manager (ASM)** | Metrics specific to their sales zone, ASO scorecard under their hierarchy, collection efficiency %, list of top 5 dealers, and zone performance. |
| **Assistant Section Officer (ASO)** | Territory metrics, counts of assigned dealers, sales vs. collections figures, list of recent orders, and list of overdue invoices. |
| **Dealer** | Outstanding balance dashboard, invoice aging summary, recent invoice list, recent payment receipts list, and active Cash Discount Slabs. |

### UAT Test Steps
1. Log in with a user of a specific role (e.g., ASM) or use the dashboard's **preview switcher** (if available to administrators) by passing `?preview_role=Area Sales Manager` in the URL.
2. **Expected Result:** Ensure the dashboard metrics match the logged-in context.
3. Validate interactive charts:
    *   **Dealer Status Breakdown:** Check active vs. inactive ratios.
    *   **Category Performance:** Check product distribution.
    *   **Upload Trend:** Check daily tracking chart.
4. Verify clicking on dashboard cards drills down into details (e.g. clicking on outstanding balance redirects to the invoices list page).

---

## 3. Dealer Management Module

### Purpose
To verify the onboarding, management, and real-time outstanding balance tracking of dealer records.

### Dealer Management Flow Table

| Step | Test Action | Expected System Behavior |
| :---: | :--- | :--- |
| **1** | Navigate to `/dealers`. | Triggers AJAX list fetch, renders dealers table. |
| **2** | Filter list by search term or account status. | Refreshes table row matches dynamically. |
| **3** | Click **+ Add Dealer** button. | Opens dealer configuration creation panel. |
| **4** | Input business profile (PAN, GST, etc.) and click **Submit**. | Performs duplicate validation. If passed, saves profile & maps to company. |
| **5** | Click **Edit** (Pencil Icon) on any row. | Pre-populates details in modal from database. |
| **6** | Modify details/status and click **Update**. | Saves edit changes, refreshes table row. |

### UAT Test Steps

#### Step 1: Listing & Filtering Dealers
1. Navigate to **Dealers** page (`/dealers`).
2. **Expected Result:** The system triggers an AJAX request (`POST /dealers/list`) and renders the dealer list table.
3. Validate columns: **Dealer Code**, **Dealer Name**, **Email**, **Phone**, **PAN**, **GST**, **Status**.
4. Verify dynamic financial calculations per dealer:
    $$\text{Outstanding Balance} = \text{Total Debit} - \text{Total Credit} + \text{Opening Balance}$$
5. Apply filters: Search by name/code, or status.
6. **Expected Result:** The table refreshes dynamically without full page reload.

#### Step 2: Onboarding a New Dealer
1. Click the **+ Add Dealer** button to open the creation form.
2. Fill in required details:
    *   **Dealer Name** (e.g., `Sharma Traders`)
    *   **Dealer Code** (e.g., `DL-SHARMA-01`)
    *   **Email** and **Phone** (10-digit number)
    *   **PAN Number** and **GST Number**
    *   **Address**
3. Click **Submit**.
4. **Expected Result:** 
    *   System validates unique fields (Dealer Code, Phone, PAN, GST).
    *   Upon successful validation, the record is stored, a company-wise mapping is created in the database mapping `dealer_id` to `active_company_id`, and the dealer table refreshes with a success notification.

#### Step 3: Editing Dealer Details
1. Find the newly created dealer in the list and click the **Edit** icon.
2. **Expected Result:** The edit modal opens with pre-populated values fetched from `GET /dealers/{encrypted_id}/edit`.
3. Update fields (e.g., edit the address or change status from **active** to **blocked** or **inactive**).
4. Click **Update**.
5. **Expected Result:** The system saves updates via `POST /dealers/{encrypted_id}/update` and refreshes the table row.

---

## 4. Inventory Management Module

### Purpose
To configure categories and product catalog items company-wise, which will be utilized during billing and sales processing.

### UAT Test Steps

#### Step 1: Category Configuration
1. Navigate to the **Categories** page (`/categories`).
2. Click **+ Add Category**.
3. Enter **Category Name** (e.g., `GP Sheet`) and **Category Code** (e.g., `GP`).
4. Click **Submit**.
5. **Expected Result:** Category is saved under the active company context. Test editing the category name and status (Active/Inactive) using the Edit modal.

#### Step 2: Product Configuration
1. Navigate to the **Products** page (`/products`).
2. Click **+ Add Product**.
3. Fill in the form fields:
    *   **Category:** Select GP Sheet from dropdown.
    *   **Product Name:** e.g., `GP Sheet 0.50mm`
    *   **SKU Code:** Unique identification string (e.g., `SKU-GP-050`)
    *   **HSN Code:** e.g., `72104900`
    *   **Size** and **Unit:** e.g., `0.50mm`, `MT` (Metric Ton)
    *   **Base Price:** e.g., `55000`
4. Click **Submit**.
5. **Expected Result:** Product is created. Verify it appears in the products table via AJAX (`POST /products/list`). Test changing its status to **inactive** or **blocked**.

#### Step 3: Product Pricing Master
1. Navigate to **Product Pricing** (`/product-pricings`).
2. Click **+ Configure Pricing**.
3. Set the parameters:
    *   **Product:** Select `GP Sheet 0.50mm`.
    *   **Price per MT:** `55000`
    *   **GST Percentage:** `18`
    *   **Effective Dates:** Select start date and end date.
4. Click **Save**.
5. **Expected Result:** Pricing takes effect immediately. Verified during invoice generation.

---

## 5. Dealer Account & Finance Module

### Purpose
To execute the end-to-end invoicing and payment lifecycle. This verify the automated cash discount slab processing, late payment penalty charges, ledger posting, and balance reconciliation.

### Account & Invoicing Lifecycle Flow Table

| Phase | Step | Action / Test Scenario | Expected System Financial Result |
| :---: | :---: | :--- | :--- |
| **Billing** | **1** | Navigate to `/accounts/invoices/generate`. | Opens billing form. Select Buyer/Ship-to, click **Save Draft**. |
| | **2** | Add SKU item and weight in MT, click **+ Add Item**. | Dynamically calculates taxable base and GST components. |
| | **3** | Click **Finalize Invoice**. | Locks invoice (read-only), creates Invoice No, posts initial `SALES` debit voucher reference to ledger. |
| **Payment** | **4** | Select finalized invoice under Payment Tracks, click **Record Voucher**. | Opens voucher entry modal. Select Voucher Type: **Receipt**. |
| | **5** | Enter receipt details and click **Submit**. | Automatically reduces outstanding dues by the payment amount. |
| **Automated Rules** | **6** | *Early Payment:* Date is within Cash Discount Slabs. | Auto-posts **Credit Note** ("By Discount & Scheme") based on rate per MT. Dues reduce further. |
| | **7** | *Late Payment:* Date is past Invoice Due Date. | Auto-posts **Debit Note** ("To Interest on Delay Payment") charging 15% p.a. daily. Dues increase. |
| **Ledger Statement** | **8** | Open **Dealer Statement**, search dealer and year. | Statement shows opening balance, invoices (debits), receipts (credits), credit notes (credits), debit notes (debits), and closing balance. |

### UAT Test Steps

#### Step 1: Generating an Invoice (Billing Flow)
1. Navigate to **Accounts -> Invoices** (`/accounts/invoices`).
2. Click **Generate Invoice** (`/accounts/invoices/generate`).
3. Select **Buyer** and **Ship To** (Choose `Sharma Traders`).
4. Select **GST %** (e.g., `18%`).
5. Click **Save Draft**.
6. **Expected Result:** Header is saved, enabling the "Add Items" form.
7. Under **Add Invoice Items**:
    *   Select **Product/Pricing** (Select `GP Sheet 0.50mm @ 55,000 / MT`).
    *   Input **Quantity in MT** (e.g., `10` MT).
    *   Click **Add Item**.
8. **Expected Result:** Line item is saved. Taxable Amount (`550,000`), GST Amount (`99,000`), and Chargeable Amount (`649,000`) calculate dynamically.
9. Review calculations, then click **Finalize Invoice**.
10. **Expected Result:** 
    *   Status updates to **Finalized**. Form changes to read-only.
    *   Unique Invoice No is assigned (e.g. `INV-2026-0001`).
    *   Invoice Payment record is initialized with an outstanding balance of `649,000`.
    *   Initial `SALES` payment track entry is recorded to charge the dealer account ledger.

#### Step 2: Payment Receipt & Automated Rule Validation
Navigate to **Accounts -> Payment Tracks** (`/accounts/payment-tracks`). Find the finalized invoice and click **Record Voucher**.

> [!NOTE]
> Testing this flow validates both cash discounts and late penalty automation based on dates.

*   **Test Case A: Cash Discount Validation (Early Payment)**
    1. Select Voucher Type: **Receipt**.
    2. Enter **Amount**: `200,000`.
    3. Select **Transaction Date**: Set date to **2 days after** the invoice generate date (within the configured 1-4 days slab).
    4. Fill in Payment Mode and click **Submit**.
    5. **Expected Result:**
        *   Outstanding balance decreases by `200,000`.
        *   System computes proportional quantity paid: $\text{payment\_for\_mt} = \frac{10 \text{ MT}}{649,000} \times 200,000 \approx 3.082 \text{ MT}$.
        *   Slab matches `1 to 4 Days` (discount rate is Rs. 700 / MT).
        *   Companion **Credit Note** is automatically generated with amount: $3.082 \text{ MT} \times 700 = 2,157.40$.
        *   Outstanding balance decreases by an additional `2,157.40`.

*   **Test Case B: Overdue Penalty Validation (Late Payment)**
    1. Select Voucher Type: **Receipt**.
    2. Enter **Amount**: `100,000`.
    3. Select **Transaction Date**: Set date to **30 days after** the invoice due date.
    4. Click **Submit**.
    5. **Expected Result:**
        *   Outstanding balance decreases by `100,000`.
        *   System computes late penalty at $15\%$ per annum: $\text{Penalty} = \frac{100,000 \times 0.15}{365} \times 30 \text{ days} \approx 1,232.88$.
        *   Companion **Debit Note** is automatically generated for `1,232.88`.
        *   Outstanding balance increases by `1,232.88`.

#### Step 3: Dealer Ledger / Statement Verification
1. Navigate to **Accounts -> Dealer Statement** (`/accounts/dealer-statement`).
2. Select **Dealer** (`Sharma Traders`).
3. Select **Financial Year** (or choose custom date range).
4. Click **Search / View Ledger**.
5. **Expected Result:**
    *   **Opening Balance:** Displays calculated historical running balance prior to the start date.
    *   **Transactions Table:** Lists all payment tracks sorted by date:
        *   `SALES` shows as **To GST Sale 18%** (Debit column increases balance).
        *   `RECEIPT` shows bank collection detail (Credit column decreases balance).
        *   `CREDIT NOTE` shows as **By Discount & Scheme** (Credit column decreases balance).
        *   `DEBIT NOTE` shows as **To Interest on Delay Payment** (Debit column increases balance).
    *   **Closing Balance:** Reflects the correct final running balance.
6. Click **Export to Excel** or **Export to PDF** and check that the downloaded files match the screen.

#### Step 4: Invoice Ageing Report
1. Navigate to **Accounts -> Ageing Report** (`/accounts/ageing-report`).
2. Select Date and filters, then click **Search**.
3. **Expected Result:** Verify that outstanding invoice balances are correctly categorized into aging brackets: **0-30 Days**, **31-60 Days**, **61-90 Days**, and **90+ Days**. Verify Excel exporting function.

---

## 6. Supplier & Purchase Management Module

### Purpose
To verify the onboarding of suppliers, logging of supplier purchase invoices, dynamic updates of product inventory stock levels upon finalization, and recording payments to suppliers.

### Supplier & Purchase Flow Table

| Step | Test Action | Expected System Behavior |
| :---: | :--- | :--- |
| **1** | Navigate to `/purchase/suppliers`. | Loads supplier directory table. |
| **2** | Click **+ Add Supplier**, fill form, click **Submit**. | Saves supplier profile (GSTIN/Phone uniqueness validated). |
| **3** | Navigate to `/purchase/invoices/generate`. | Opens purchase invoice draft billing workspace. |
| **4** | Select Supplier, GST %, click **Save Draft**. | Creates purchase invoice draft header. |
| **5** | Select Product SKU, input Price & Quantity, click **+ Add Item**. | Dynamically logs purchase invoice details. |
| **6** | Click **Finalize Purchase Invoice**. | Bill locks. Product stock levels in inventory (`stock_quantity`) increase by purchase quantity. |
| **7** | Click **Record Payment** on finalized row, fill payment info. | Reduces outstanding payments owed to the supplier. |
| **8** | Navigate to `/stocks`. | Product table displays the updated product inventory stock count. |

### UAT Test Steps

#### Step 1: Onboarding a Supplier
1. Navigate to **Purchase -> Suppliers** (`/purchase/suppliers`).
2. Click **+ Add Supplier** to open the panel.
3. Fill details: **Supplier Name** (e.g. `Tata Steel`), **GSTIN** (unique number), **Phone**, **Email**, and **Address**.
4. Click **Submit**.
5. **Expected Result:** Supplier details are saved, and the suppliers index table refreshes.

#### Step 2: Creating a Purchase Invoice & Finalizing (Inventory & Stock Increment Check)
1. Navigate to **Purchase -> Purchase Invoices** (`/purchase/invoices`).
2. Click **Generate Purchase** (`/purchase/invoices/generate`).
3. Select **Supplier** (`Tata Steel`) and set **GST%** to `18%`. Click **Save Draft**.
4. Under **Invoice Items**, select product SKU (`GP Sheet 0.50mm`), price per MT (`50000`), and enter Quantity (`25` MT). Click **+ Add Item**.
5. Click **Finalize Purchase Invoice**.
6. **Expected Result:** 
    *   The purchase invoice moves to **Finalized** state.
    *   Outstanding amount to the supplier is initialized.
    *   Navigate to **Inventory -> Stocks** (`/stocks`). Verify that the **Stock Quantity** of `GP Sheet 0.50mm` has increased by `25` MT.

#### Step 3: Stock Adjustments
1. Navigate to **Inventory -> Stocks** (`/stocks`).
2. Find product `GP Sheet 0.50mm`, click **Adjust Stock**.
3. Select type as **Add** or **Subtract**, enter quantity (e.g. `2` MT) and **Remarks** (e.g. `Damage scrap adjustment`), and submit.
4. **Expected Result:** Stock level is updated dynamically based on the adjustment.

---

## 7. Settings Module

### Purpose
To verify the security password changes, ensuring current password checks and complex validation filters.

### Settings Flow Table

| Step | Test Action | Expected System Behavior |
| :---: | :--- | :--- |
| **1** | Navigate to `/settings/password`. | Change password form loads. |
| **2** | Enter wrong current password, click **Submit**. | Rejects with message: "The provided current password does not match our records." |
| **3** | Enter short new password (less than 8 characters). | Rejects with message: "The new password must be at least 8 characters." |
| **4** | Enter current password as new password. | Rejects with message: "New password cannot be the same as your current password." |
| **5** | Enter matching current, valid new (>=8 chars), and confirm password, click **Submit**. | Saves the new password and returns success message. |

### UAT Test Steps

#### Step 1: Password Update Verification
1. Navigate to **Settings -> Change Password** (`/settings/password`).
2. Type in current password fields and test validation boundaries as shown in the settings flow table.
3. Verify that successful submission saves the hashed password, and subsequent logins require the new password.

---

## 8. User Management Module

### Purpose
To verify manual user additions, Excel bulk user uploads (with background processing), and user mappings/allocations (Company and Role setup).

### User Management Flow Table

| Step | Test Action | Expected System Behavior |
| :---: | :--- | :--- |
| **1** | Navigate to `/users`. | Renders user list table. |
| **2** | Click **+ Add User**, fill form and click **Submit**. | Saves the user profile. |
| **3** | Navigate to `/users/uploads`. | Renders background file import tracking workspace. |
| **4** | Click **Download Template**, populate rows, select file and click **Import**. | Uploads file to local disk, logs import request, and triggers background import queue. |
| **5** | Monitor background list page and refresh. | Displays status change: Pending ➔ Processing ➔ Completed. If failure, errors link is available. |
| **6** | Navigate to `/users/roles`. | Renders user allocations directory. |
| **7** | Click **Mappings** on target user row. | Displays current company & role links. |
| **8** | Click **+ Add Allocation**, select Company & Role context and click **Save**. | Maps permissions context for the user. |

### UAT Test Steps

#### Step 1: User Onboarding (Manual & Excel Upload)
1. Navigate to **Users** (`/users`). Click **+ Add User** to manually add a test user and check it shows in the table list.
2. Go to **Users -> Upload Users** (`/users/uploads`).
3. Click **Download Template**, open template, and insert rows (Name, Phone, Email, Password). Save.
4. Click **Select File** on page, choose file, and click **Import**.
5. **Expected Result:** Success notification is shown. File status logs as "Pending" in the upload history table, which switches to "Completed" on background processing.

#### Step 2: Role and Company Allocation (Mappings)
1. Navigate to **Users -> User Roles** (`/users/roles`).
2. Locate user and click **Mappings** to open permissions list.
3. Click **+ Add Allocation**, choose Company and Role from dropdown, and click **Save**.
4. **Expected Result:** Allocation displays in table context. Ensure you can log in as this user and switch to this company context.

---

> [!TIP]
> **UAT Sign-off Checklist:**
> - [ ] Context switcher switches roles and restricts data visibility accordingly.
> - [ ] Duplicate PAN/GST dealer entry returns a handled error alert.
> - [ ] Product prices and base parameters are locked during billing after invoice finalization.
> - [ ] Receipt entry triggers the correct calculation of Cash Discount and Overdue Penalty.
> - [ ] Dynamic ledger opening/closing matches physical invoice tallies.
> - [ ] Finalizing purchase invoice automatically increases product stock counts.
> - [ ] Manual stock adjustments update inventory quantities with remarks.
> - [ ] Password update page validates security thresholds and successfully saves new passwords.
> - [ ] Excel bulk user upload processes successfully in the background.
> - [ ] Dynamic role allocations successfully map permissions to users.
