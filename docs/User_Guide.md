# Dealer Management & Finance System
## Easy Everyday User Guide (Non-Technical Manual)

Welcome! This guide is written in plain English to help you use the system for daily work. You don't need to know technical coding terms. Just follow the step-by-step arrow sequences and simple rules.

---

## What is in this Guide?
*   **Step 1: Logging In & Selecting Your Company (Getting Started)**
*   **Step 2: Onboarding Your Dealers (Adding, Editing & Excel Upload)**
*   **Step 3: Managing Your Products & Selling Prices (Adding & Excel Upload)**
*   **Step 4: Billing a Dealer (Creating Invoices & Excel Upload)**
*   **Step 5: Recording Payments & Automated Helpers (Voucher Creation & Excel Upload)**
*   **Step 6: Reading a Dealer Ledger & Ageing Reports (Account Statements & Reports)**
*   **Step 7: Supplier & Purchase Management (Onboarding Suppliers, Stock Entry & Stock Ledger)**
*   **Step 8: Account Settings (Updating Your Password)**
*   **Step 9: User Management & Allocations (Creating Users, Excel Upload & Role Mappings)**
*   **Step 10: WhatsApp Payment Reminders (Automated Background Alerts & Manual Reminders)**

---

## Step 1: Logging In & Selecting Your Company

If your account is assigned to work with multiple companies (like JSW and Eskay Marketing), you will choose which company context you want to log into.

### Step-by-Step Action Table

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Open your browser and navigate to the system URL. | The **Welcome Back!** Login screen appears. |
| **2** | Enter your **Phone Number** & **Password**, then click **Login**. | *   If you have **1 company mapping**: you land directly on the **Dashboard**.<br>*   If you have **multiple mappings**: the **Select Role & Company** window pops up. |
| **3** | Choose the active Company and Role from the dropdown and click **Continue**. | Your session is set up and you land on the **Home Dashboard**. |
| **4** | *Optional:* Click **Switch Context** on the top navbar at any time. | The company/role dropdown opens to allow context switching. |

**Quick Flow:**
`Open Browser` ➔ `Enter Phone & Password` ➔ `Click Login` ➔ `Choose Company Context (if asked)` ➔ `Land on Dashboard`

### 💡 Simple Explanations:
*   **Active Context:** This means the company you are currently looking at. If you switch context to JSW, you will only see JSW dealers and invoices. If you switch to Eskay Marketing, you will only see Eskay's data.

---

## Step 2: Onboarding Your Dealers (Adding, Editing & Excel Upload)

Keep your dealer list up to date. You can add dealers manually one-by-one, edit profiles, or import them all at once using an Excel spreadsheet.

### A. Manual Entry Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Click **Dealers** in the sidebar. | The dealers list page loads, displaying current accounts and outstanding balances. |
| **2** | Click the blue **+ Add Dealer** button. | A slide-out panel or popup form opens. |
| **3** | Fill out the form fields (Name, Code, Phone, PAN, GST, Address). | The form is completed and validated. |
| **4** | Click **Submit**. | System checks for duplicate PAN/GST. If unique, saves the dealer and refreshes the list table. |
| **5** | Locate a dealer and click the **Edit** icon (Pencil). | Opens pre-populated form where you can update details or set status (Active, Inactive, Blocked). |

**Quick Flow:**
`Click Dealers` ➔ `Click + Add Dealer` ➔ `Fill Name, Code, Phone, PAN, GST` ➔ `Click Submit` ➔ `Dealer Appears in List`

### B. Bulk Upload via Excel Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Click **Dealers** ➔ **Upload Dealers** in sidebar menu. | The Dealer Excel Upload tracker screen loads. |
| **2** | Click **Download Template** button. | The standard blank Excel file downloads to your computer. |
| **3** | Open the downloaded Excel file and fill in columns with your dealers' details. | spreadsheet is populated with Name, Code, PAN, GST, and Address rows. |
| **4** | Click **Select File** on page, choose your Excel spreadsheet, and click **Import**. | System uploads, runs checks, and displays results in the log feed. |
| **5** | *Audit Check:* Look at the Upload History log at the bottom. | If success: all rows added. If failure: click **Download Error Report** to see which row has a typo (e.g. duplicate code), fix it, and re-upload. |

---

## Step 3: Managing Your Products & Selling Prices (Adding & Excel Upload)

Set up the product catalogs and establish selling rates per Metric Ton (MT).

### A. Manual Catalog Entry Step-by-Step

| Task | Step | Action to Take | Expected Result |
| :--- | :---: | :--- | :--- |
| **Setup Categories** | **1** | Go to **Inventory** ➔ **Categories** | Opens the categories configuration panel. |
| | **2** | Click **+ Add Category** | Enter category name & code, and click **Submit** to save category. |
| **Setup Products** | **3** | Go to **Inventory** ➔ **Products** | Opens the product catalog panel. |
| | **4** | Click **+ Add Product** | Select Category, enter SKU/HSN, size, unit, and click **Submit**. |
| **Setup Pricing** | **5** | Go to **Inventory** ➔ **Product Pricing** | Opens the pricing manager panel. |
| | **6** | Click **+ Configure Pricing** | Select Product, enter price per Ton, GST%, set dates, and click **Save**. |

**Quick Flow:**
`Click Inventory` ➔ `Add Category` ➔ `Add Product SKU` ➔ `Configure MT Rate & GST %` ➔ `Save Price`

### B. Bulk Upload Products via Excel Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Inventory** ➔ **Inventory Upload** in the sidebar. | The Product catalog bulk upload manager panel loads. |
| **2** | Click **Download Template** to get the standard spreadsheet file. | The template downloads to your computer. |
| **3** | Populate the columns in the spreadsheet: Category Code, Product Name, SKU, HSN, Unit, and Base Price. | spreadsheet is ready for upload. |
| **4** | Click **Select File**, browse and select your sheet, and click **Import**. | Upload runs. Valid products are added; any failures are logged at the bottom. |

---

## Step 4: Billing a Dealer (Creating Invoices & Excel Upload)

Generate sales invoices for dealers. You can draft bills manually online, or import bulk bills from an Excel sheet.

### A. Manual Invoicing Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Accounts** ➔ **Invoices** and click **Generate Invoice**. | The invoice billing form opens. |
| **2** | Select the **Buyer** (Dealer), **Ship-To** address, and **GST%**. | Click **Save Draft** to initialize the invoice. |
| **3** | Choose product SKU and enter **Quantity in Tons (MT)**. | Click **+ Add Item** to add the row to the invoice. |
| **4** | Add more items if needed, and review calculations. | Taxes (CGST/SGST) and totals calculate dynamically. |
| **5** | Click the **Finalize Invoice** button. | Invoice is locked, assigned a unique bill number, and posted to the dealer ledger. |

**Quick Flow:**
`Click Invoices` ➔ `Click Generate Invoice` ➔ `Select Buyer & GST%` ➔ `Click Save Draft` ➔ `Add SKU & Quantity` ➔ `Click Finalize Invoice`

### B. Bulk Upload Invoices via Excel Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Accounts** ➔ **Upload Excel** in sidebar menu. | The bulk Excel upload console opens. |
| **2** | Select the **Invoices** tab and click **Download Template**. | The blank billing template downloads to your computer. |
| **3** | Open sheet and fill columns: Invoice No, Date, Buyer Code, SKU Code, Quantity (MT), Rates, and GST%. | Sheet populated with multiple invoices. |
| **4** | Choose file under the Invoice section and click **Import Invoices**. | The system imports bills, verifies balances, and displays success/error log. |

---

## Step 5: Recording Payments & Automated Helpers (Voucher Creation & Excel Upload)

When a dealer pays you, record the receipt. The system handles the complex financial calculations (discounts & interest fees) automatically. You can do this one-by-one or in bulk via spreadsheet.

### A. Manual Voucher Entry Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Accounts** ➔ **Payment Tracks**. | The billing tracker loads. |
| **2** | Find the finalized invoice and click **Record Voucher**. | The payment popup panel opens. |
| **3** | Select **Receipt** as Voucher Type. | Enables the standard receipt entry fields. |
| **4** | Enter Amount, Transaction Date, and Payment Mode (RTGS, Cash, Cheque) | Click **Submit**. Dynamic outstanding dues are reduced. |
| **5** | **Early Payment Reward:** Check if payment falls in discount slab. | System auto-posts a **Credit Note** ("By Discount & Scheme") to award discount. |
| **6** | **Late Payment Penalty:** Check if payment date is past due date. | System auto-posts a **Debit Note** ("To Interest on Delay Payment") to charge 15% p.a. penalty. |

**Quick Flow:**
`Click Payment Tracks` ➔ `Click Record Voucher` ➔ `Select Receipt & Enter Amount` ➔ `Click Submit` ➔ `System checks dates` ➔ `Auto-generates Credit Note (Discount) OR Debit Note (Penalty)`

### B. Bulk Upload Vouchers via Excel Step-by-Step

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Accounts** ➔ **Upload Excel** in sidebar menu. | The upload console loads. |
| **2** | Select the **Vouchers** tab and click **Download Template**. | The payment template downloads to your computer. |
| **3** | Populate columns: Invoice No, Transaction Date, Paid Amount, Payment Mode, and Remarks. | Vouchers spreadsheet is prepared. |
| **4** | Choose file under Voucher section and click **Import Vouchers**. | The system processes transactions, adjusts outstanding dues, and logs results. |

---

## Step 6: Reading a Dealer Ledger & Ageing Reports (Account Statements & Reports)

Think of a dealer statement like a bank passbook. It lists every transaction (debits and credits) between you and the dealer. You can also view the Invoice Ageing Report to see how long outstanding balances have been overdue.

### A. Reading the Dealer Statement (Ledger)

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Accounts** ➔ **Dealer Statement**. | The account ledger filter screen appears. |
| **2** | Select the **Dealer Name** and the **Financial Year** or custom date range. | The query is set up. |
| **3** | Click **Search / View Ledger**. | The statement ledger loads with opening balance, transaction list, and closing balance. |
| **4** | Click **Export to PDF** or **Export to Excel** in top menu. | Downloads printable PDF statement or raw Excel spreadsheet. |

**Quick Flow:**
`Click Dealer Statement` ➔ `Select Dealer Name & Dates` ➔ `Click Search` ➔ `Review Balances` ➔ `Export to PDF / Excel`

### B. Checking the Invoice Ageing Report

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Accounts** ➔ **Ageing Report** in sidebar menu. | The Ageing Report filter panel loads. |
| **2** | Select the target Date filter and any optional dealer/company criteria. | Filters configured. |
| **3** | Click **Search / View Report**. | A table displays listing each dealer's total outstanding balance categorized into aging buckets: **0-30 Days**, **31-60 Days**, **61-90 Days**, and **90+ Days**. |
| **4** | Click **Export to Excel** in the top menu. | Downloads the structured aging spreadsheet for your reports. |

### 💡 Reading the Ledger Transactions:
*   **Opening Balance:** The initial amount they owed before this period started.
*   **"To GST Sale 18%" (SALES):** A bill we sent them. This **increases** what they owe.
*   **"RECEIPT" (Payments):** Money they paid us. This **decreases** what they owe.
*   **"By Discount & Scheme" (CREDIT NOTE):** Early payment discounts or refunds. This **decreases** what they owe.
*   **"To Interest on Delay Payment" (DEBIT NOTE):** Penalty interest charges. This **increases** what they owe.
*   **Closing Balance:** The final, up-to-date amount they owe you at the end of the selected dates.

---

## Step 7: Supplier & Purchase Management (Onboarding Suppliers, Stock Entry & Stock Ledger)

Manage purchases from your suppliers. Recording a purchase invoice adds raw material stock counts to your inventory catalog automatically.

### Step-by-Step Action Table

| Task | Step | Action to Take | Expected Result |
| :--- | :---: | :--- | :--- |
| **Add Supplier** | **1** | Go to **Purchase** ➔ **Suppliers** and click **+ Add Supplier** | Opens Supplier form. Fill Name, GSTIN, Phone, Address, and click **Submit**. |
| **Receive Materials** | **2** | Go to **Purchase** ➔ **Purchase Invoices** and click **Generate Purchase** | Select Supplier and GST %, and click **Save Draft**. |
| | **3** | Under Invoice Items: Select Product SKU, input Price per MT and Quantity (MT) | Click **+ Add Item** to link products. |
| | **4** | Click **Finalize Purchase Invoice** | Locks purchase invoice. Dynamic supplier outstanding balances are recorded, and product **Stock Quantity** increases instantly in inventory. |
| **Pay Supplier** | **5** | Click **Record Payment** on finalized purchase invoice row | Enter payment details and submit to reduce dues owed to the supplier. |
| **Check stock levels** | **6** | Go to **Inventory** ➔ **Stocks** (or Stock Ledger) | Displays active stock levels per product. Use **Adjust Stock** button to manually add/subtract stock weight with audit remarks. |

**Quick Flow:**
`Add Supplier` ➔ `Generate Purchase Bill` ➔ `Add Materials & Weight (MT)` ➔ `Finalize Bill (Stock Increments)` ➔ `Track Stock levels in Ledger`

---

## Step 8: Account Settings (Updating Your Password)

Manage your user account password settings to keep your account secure.

### Step-by-Step Action Table

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Settings** ➔ **Change Password** (or navigate to `/settings/password`). | The Change Password form displays. |
| **2** | Enter your **Current Password**. | Required to authenticate your identity. |
| **3** | Enter your **New Password** and **Confirm Password** (must be at least 8 characters long). | Ensures new password is complex and confirms accurate spelling. |
| **4** | Click **Update Password** (or **Submit**). | The system checks credentials, saves the updated password, and displays a success alert. |

**Quick Flow:**
`Go to Settings` ➔ `Enter Current Password` ➔ `Type New Password (>= 8 chars)` ➔ `Confirm & Click Update`

---

## Step 9: User Management & Allocations (Creating Users, Excel Upload & Role Mappings)

Manage administrative and sales team user accounts. You can create users manually, bulk import users via Excel, or allocate role contexts (companies, roles, and reporting manager hierarchies) to them.

### A. Manual User Creation & Allocations

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Users** ➔ **Users List** (or `/users`). | The users directory page loads. |
| **2** | Click **+ Add User** button. | A modal form displays. Fill Name, Phone, Email, Password and click **Submit**. |
| **3** | In users list row, click **Mappings** (or **Allocate**). | Opens Role/Company allocation view for that user. |
| **4** | Click **+ Add Allocation**, select Company, Role, and **Reporting To (Supervisor)** if applicable, then click **Save**. | Maps permissions context and reporting manager hierarchy (e.g. Area Sales Manager reporting to an ASM or Branch Manager) for the user. |
| **5** | Modify or delete allocations using action items in the mappings list. | Permission tables update instantly. |

### B. Bulk Upload Users via Excel

| Step | Action to Take | Expected Result |
| :---: | :--- | :--- |
| **1** | Go to **Users** ➔ **Upload Users** (or `/users/uploads`). | The User Excel Upload panel opens. |
| **2** | Click **Download Template** button. | Blank user import spreadsheet downloads. |
| **3** | Open spreadsheet: Populate Name, Phone, Email, and Password columns. | User sheet is prepared. |
| **4** | Select your populated Excel file and click **Import**. | File uploads to queue for background processing. |
| **5** | Check progress in log. If failure occurs, click **Download Error Report** to identify invalid rows. | Displays real-time task status. |

---

## Step 10: WhatsApp Payment Reminders (Automated Background Alerts & Manual Reminders)

Keep track of overdue payments by sending reminders to dealers via WhatsApp. The system supports both automatic background reminders and manual click-to-send reminders.

### Step-by-Step Action Table

| Method | Type / Stage | Trigger Conditions | WhatsApp Message Template |
| :--- | :---: | :--- | :--- |
| **Automated Background Reminders** | **Stage 1** (Initial Alert) | Unpaid invoice is **1 to 2 days** past its due date. | "Dear *[Dealer Name]*, your invoice *[Invoice No]* with due date [Due Date] is now overdue. Outstanding amount: INR [Amount]. Please clear the payment." |
| | **Stage 2** (Follow-up) | Unpaid invoice is **3 to 9 days** past its due date. | "Dear *[Dealer Name]*, this is a follow-up reminder that invoice *[Invoice No]* is overdue by [Days] days. Outstanding amount: INR [Amount]. Please make the payment." |
| | **Stage 3** (Final Alert) | Unpaid invoice is **10+ days** past its due date. | "Dear *[Dealer Name]*, this is a final reminder that invoice *[Invoice No]* is overdue by [Days] days. Outstanding amount: INR [Amount]. Please settle it immediately." |
| **Manual Click-to-Send Reminders** | Manual (One-Click) | Click green **Reminder** (WhatsApp icon) on any overdue invoice row under **Accounts** ➔ **Ageing Report**. | Opens WhatsApp Web or Desktop with a pre-filled, customized payment reminder message. You only need to review and click **Send**. |
