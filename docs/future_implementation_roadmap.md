# Future Implementation Roadmap: NE INFRA DMS

This document outlines the planned future enhancements and structural improvements for the Dealer Management System. It serves as a blueprint for scaling the application's functionality, usability, and performance.

---

## 1. Advanced Filtering on Data Pages
To improve data discoverability, comprehensive filters will be added to all data-heavy pages. These will be implemented using DataTables server-side processing or AJAX-based filter forms.

*   **Order & Accounts (Payment Tracks, Credit/Debit Notes):**
    *   **Date Range Picker:** Filter records from `Start Date` to `End Date`.
    *   **Status Toggles:** Filter by Pending, Approved, Rejected, or Completed.
    *   **Dealer/Customer Search:** Searchable dropdown to filter records by specific dealers.
*   **Inventory (Products/Categories):**
    *   **Category Filter:** Dropdown to view products by category.
*   **Dealer Management:**
    *   **Account Status:** Active vs. Inactive dealers.

---

## 2. Expanded Settings Sub-Menu
Currently, the Settings menu primarily handles password changes. To make the system more dynamic, the following administrative sub-menus should be added:

*   **Role & Permission Manager:** A visual matrix interface for Admins to dynamically check/uncheck permissions for specific roles, replacing hardcoded checks.
*   **System Preferences:** Configure global settings such as application name, company logos, default currency, and timezone.
*   **Notification Settings:** Interfaces to manage Email and SMS templates, and toggle specific system notifications on or off.

---

## 3. Dashboard Data Visualizations (Graphs & Charts)
The dashboard will be upgraded from simple metric cards to a highly visual, interactive command center using libraries like Chart.js or ApexCharts.

*   **Revenue & Sales Trend (Line/Area Chart):** Visualize weekly or monthly sales performance.
*   **Payment Collection Status (Pie/Doughnut Chart):** Display the ratio of 'Received Payments' vs. 'Outstanding/Pending Dues'.
*   **Top Performing Dealers (Bar Chart):** Rank the top 5 or 10 dealers based on order volume or revenue generated.
*   **Recent Activity Feed:** A scrolling ticker or list showing the latest system events (e.g., "New Order #123 Placed", "Payment Received from Dealer X").

---

## 4. Company Management Section (Admin Only)
Since the system handles multiple companies (e.g., JSW, NE INFRA), a dedicated module is required to manage them effectively.

*   **Company CRUD:** Interface to Add, Edit, and Deactivate subsidiary companies.
*   **Company Profiles:** Store crucial data such as Legal Name, GSTIN, PAN, registered addresses, and primary contact details.

---

## 5. Advanced User Management (Admin Only)
Expanding the existing user list into a robust management console.

*   **Bulk User Operations:** Capability to upload multiple users via CSV/Excel, and bulk-activate or deactivate accounts.
*   **Admin Override:** Ability for admins to trigger password reset emails or manually override passwords for locked-out users.
