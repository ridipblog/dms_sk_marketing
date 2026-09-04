Prepared based on the uploaded Dealer Finance client understanding document. 

# Business Requirement Document

## Dealer Management & Finance Automation System

**Document Version:** 1.0
**Prepared For:** North East Infra Networks / Eskay Marketing
**Prepared By:** Development Team
**Document Type:** Business Requirement Document
**Project Type:** Web-based Dealer Management, Finance Automation, and MIS System

---

## 1. Executive Summary

The proposed **Dealer Management System** is a centralized web-based platform designed to automate dealer operations, order management, payment tracking, finance calculations, Credit Note and Debit Note processing, cash discount management, and MIS reporting.

Currently, the client manages most business and finance operations through Excel sheets and manual calculations. This creates challenges such as duplicate data entry, delayed reconciliation, calculation errors, lack of real-time visibility, and dependency on individual users for reporting and financial tracking.

The proposed system will replace manual Excel-based workflows with a structured, secure, and scalable application. It will help the client manage dealer records, product pricing, orders, invoices, payments, outstanding balances, cash discounts, penalties, Credit Notes, Debit Notes, and management reports from a centralized system.

The system will also support two-company operations, role-based access control, audit tracking, WhatsApp notifications, and future integration with Tally.

---

## 2. Business Objectives

The key objectives of the proposed system are:

1. To reduce manual Excel dependency in dealer and finance operations.
2. To centralize dealer, product, order, payment, and finance data.
3. To automate cash discount and overdue penalty calculations.
4. To generate Credit Notes and Debit Notes based on defined business rules.
5. To improve payment tracking and outstanding monitoring.
6. To provide real-time MIS reports and finance dashboards.
7. To support secure two-company operations within one application.
8. To reduce finance reconciliation delays and calculation errors.
9. To improve management visibility over sales, payments, outstanding, and dealer performance.
10. To prepare the system for future Tally API integration.

---

## 3. Current Business Challenges

The client currently faces the following challenges:

1. Dealer data is maintained manually across Excel files.
2. Product pricing and applicable rates are handled manually.
3. Order and invoice tracking is not fully centralized.
4. Payment records and outstanding balances require manual reconciliation.
5. Cash discount eligibility is calculated manually.
6. Overdue penalties are manually checked and calculated.
7. Credit Notes and Debit Notes are prepared manually.
8. Management reports depend on manually prepared Excel sheets.
9. Two-company operations create duplicate work and reporting complexity.
10. There is limited audit visibility on who changed what and when.
11. Payment follow-ups are handled manually, causing delays.

---

## 4. Proposed Solution

The proposed solution is a centralized Dealer Management and Finance Automation System with the following core capabilities:

1. Dealer master management
2. Product and pricing management
3. Dealer order management
4. Invoice and dispatch tracking support
5. Payment and outstanding management
6. Cash discount slab management
7. Automatic Credit Note generation
8. Automatic Debit Note generation
9. Dealer ledger and finance transaction tracking
10. WhatsApp-based payment reminders and alerts
11. MIS reports and finance dashboards
12. Company-wise data segregation
13. Role-based access control
14. Audit logs and activity tracking

---

## 5. Project Scope

### 5.1 In Scope

The following features are included in the initial project scope:

1. Company management for two-company operations
2. User and role management
3. Dealer management
4. Product and SKU management
5. Product pricing configuration
6. Dealer-wise order entry
7. Order status tracking
8. Invoice reference management
9. Payment entry and tracking
10. Outstanding balance calculation
11. Payment aging analysis
12. Cash discount slab configuration
13. Credit Note generation
14. Debit Note generation
15. Dealer ledger management
16. WhatsApp notification support
17. MIS reports and dashboards
18. Excel import and export
19. PDF export for selected reports
20. Audit log tracking
21. Company-wise access control

---

### 5.2 Out of Scope for Initial Phase

The following items may be considered future scope unless separately approved:

1. Direct Tally API integration
2. Full accounting software replacement
3. GST return filing
4. E-invoice generation
5. E-way bill integration
6. Advanced warehouse management
7. Mobile app development
8. Dealer self-service portal
9. Payment gateway integration
10. Advanced BI dashboards
11. Automated bank statement reconciliation
12. Dispatch/logistics API integration

---

## 6. Stakeholders

| Stakeholder                 | Responsibility                                                 |
| --------------------------- | -------------------------------------------------------------- |
| Business Owner / Management | Final approval, business rules, reporting requirements         |
| Finance Team                | Payment tracking, reconciliation, Credit/Debit Note validation |
| Sales Team                  | Dealer coordination, order entry, payment follow-up            |
| Admin Users                 | Master data management and system configuration                |
| Super Admin                 | Company, user, role, and global system control                 |
| Development Team            | System design, development, testing, deployment                |
| Client SPOC                 | Requirement clarification and user acceptance approval         |

---

## 7. User Roles

The system should support role-based access control.

### 7.1 Suggested Roles

1. **Super Admin**

   * Full system access
   * Can manage both companies
   * Can manage users, roles, settings, and reports

2. **Company Admin**

   * Access to assigned company only
   * Can manage company-specific dealers, products, orders, and finance records

3. **Finance User**

   * Can manage payments, outstanding, Credit Notes, Debit Notes, and finance reports

4. **Sales User**

   * Can manage dealers, orders, and payment follow-ups

5. **Reporting User / Management User**

   * Can view dashboards and reports
   * Limited data modification access

6. **Data Entry User**

   * Can enter records based on assigned permission
   * Limited approval or finance access

---

## 8. Functional Requirements

---

# 8.1 Company Management Module

The system shall support two-company operations within a single centralized platform.

### Key Requirements

1. The system shall allow creation and management of company records.
2. Each company shall have separate dealers, products, pricing, orders, invoices, payments, and reports.
3. Users shall be assigned to one or more companies.
4. Company-wise data isolation shall be maintained.
5. One company’s users shall not access another company’s data unless permission is explicitly granted.
6. Super Admin shall have centralized access to both companies.

### Expected Outcome

This module will reduce duplication while maintaining secure company-wise business separation.

---

# 8.2 Dealer Management Module

The Dealer Management Module will maintain complete dealer profiles and related business information.

### Key Requirements

1. Add, edit, view, and deactivate dealer records.
2. Store dealer name, business name, contact person, mobile number, email, address, GST number, PAN number, and other statutory details.
3. Map dealers to a specific company.
4. Configure dealer-wise credit limit.
5. Configure dealer-wise payment terms.
6. Track dealer status as active/inactive/blocked.
7. Maintain dealer-wise transaction history.
8. Support Excel import and export for dealer data.
9. Prevent duplicate dealer records based on defined rules such as GST number, PAN number, or mobile number.

### Expected Outcome

The client will have a clean, centralized, and searchable dealer database.

---

# 8.3 Product & Pricing Management Module

This module will maintain product, SKU, category, and pricing information.

### Key Requirements

1. Add and manage product categories.
2. Add and manage product SKUs.
3. Maintain product unit of measurement, such as MT.
4. Configure company-wise product pricing.
5. Configure dealer/product applicability if required.
6. Maintain pricing history.
7. Support bulk upload and update of product pricing.
8. Allow authorized users to update pricing.
9. Track price changes through audit logs.

### Expected Outcome

The system will reduce manual price lookup and pricing errors during order and finance processing.

---

# 8.4 Order Management Module

The Order Management Module will manage dealer orders from entry to invoice reference and dispatch tracking.

### Key Requirements

1. Create dealer-wise orders.
2. Select company, dealer, product, quantity, rate, and order date.
3. Support quantity entry in MT.
4. Calculate order amount automatically based on rate and quantity.
5. Track order status.
6. Support order approval workflow if required.
7. Convert order to invoice record or map order with invoice details.
8. Maintain invoice number, invoice date, invoice amount, and due date.
9. Support dispatch tracking information if required.
10. Maintain full order history.

### Suggested Order Statuses

1. Draft
2. Submitted
3. Approved
4. Invoiced
5. Dispatched
6. Completed
7. Cancelled

### Expected Outcome

The system will create a structured order flow and improve coordination between sales, dispatch, and finance teams.

---

# 8.5 Finance & Payment Management Module

The Finance & Payment Management Module is the core module of the system. It will manage payment entries, outstanding balances, dealer reconciliation, discount eligibility, penalty calculation, and ledger updates.

### Key Requirements

1. Record dealer payments against invoices.
2. Allow full or partial payment entry.
3. Track invoice amount, paid amount, pending amount, and outstanding balance.
4. Calculate payment due date based on configured payment terms.
5. Track payment aging.
6. Identify overdue invoices.
7. Calculate cash discount eligibility.
8. Calculate overdue penalty where applicable.
9. Generate Credit Notes and Debit Notes automatically or through approval-based workflow.
10. Maintain dealer-wise ledger.
11. Provide finance dashboard and reports.
12. Support manual adjustment entries with proper approval and audit log.
13. Maintain complete finance transaction history.

### Expected Outcome

This module will reduce manual reconciliation effort and improve finance accuracy.

---

# 8.6 Cash Discount Management Module

The system shall support configurable cash discount slabs based on dealer payment timelines.

### Initial Cash Discount Rules

| Payment Timeline | Cash Discount |
| ---------------- | ------------: |
| Advance Payment  |  Rs. 900 / MT |
| 1 to 4 Days      |  Rs. 700 / MT |
| 5 to 10 Days     |  Rs. 500 / MT |
| 11 to 15 Days    |  Rs. 300 / MT |
| 16 to 20 Days    |  Rs. 100 / MT |

### Key Requirements

1. The system shall allow configuration of cash discount slabs.
2. Discount shall be calculated based on payment date, invoice date/due date, quantity, and business rule.
3. Discount amount shall be calculated automatically per MT.
4. Discount eligibility shall be clearly visible to finance users.
5. Approved discount shall be adjusted through Credit Note.
6. Discount calculation history shall be stored.
7. Any change in slab rules shall be tracked through audit logs.

### Expected Outcome

The system will remove manual discount calculation and ensure consistent finance treatment.

---

# 8.7 Debit Note Penalty Management

After the allowed due date, the system shall calculate penalty automatically.

### Initial Penalty Rule

| Condition                               |           Penalty |
| --------------------------------------- | ----------------: |
| Payment delayed beyond allowed due date | Rs. 30 / MT / Day |

### Key Requirements

1. The system shall identify overdue invoices.
2. Penalty shall be calculated based on overdue days and quantity in MT.
3. Penalty shall be calculated automatically.
4. Debit Note shall be generated for penalty amount.
5. Finance team shall be able to review and approve Debit Notes if approval workflow is enabled.
6. Dealer ledger shall be updated after Debit Note generation.
7. Penalty calculation details shall be stored for audit purposes.

### Expected Outcome

The system will ensure timely and accurate penalty calculation for delayed payments.

---

# 8.8 Credit Note Management

Credit Notes shall be generated for eligible dealer benefits, mainly cash discount benefits.

### Key Requirements

1. Generate Credit Note when dealer payment qualifies for cash discount.
2. Calculate eligible amount automatically based on payment timeline and MT quantity.
3. Link Credit Note with invoice, dealer, company, payment, and ledger.
4. Allow finance review before final posting if required.
5. Maintain Credit Note number, date, amount, reason, and status.
6. Update dealer ledger after Credit Note approval/posting.
7. Allow PDF/export generation if required.
8. Maintain complete Credit Note history.

### Suggested Credit Note Statuses

1. Draft
2. Pending Approval
3. Approved
4. Posted
5. Cancelled

### Expected Outcome

Credit Note processing will become faster, consistent, and traceable.

---

# 8.9 Debit Note Management

Debit Notes shall be generated for overdue penalties or other dealer recoverable amounts.

### Key Requirements

1. Generate Debit Note for overdue payment penalty.
2. Support manual Debit Note creation for approved business cases.
3. Link Debit Note with dealer, company, invoice, payment, and ledger.
4. Calculate penalty automatically based on business rules.
5. Allow finance review and approval if required.
6. Update outstanding and dealer ledger after posting.
7. Maintain Debit Note number, date, amount, reason, and status.
8. Maintain complete Debit Note history.

### Suggested Debit Note Statuses

1. Draft
2. Pending Approval
3. Approved
4. Posted
5. Cancelled

### Expected Outcome

Debit Note processing will become automated, transparent, and auditable.

---

# 8.10 Dealer Ledger Management

The system shall maintain a complete dealer ledger.

### Key Requirements

1. Show dealer-wise opening balance.
2. Show invoice entries.
3. Show payment entries.
4. Show Credit Note entries.
5. Show Debit Note entries.
6. Show adjustments if any.
7. Show closing balance.
8. Filter ledger by date range, company, dealer, and transaction type.
9. Export ledger to Excel/PDF.
10. Maintain transaction reference for every ledger entry.

### Expected Outcome

Finance users can easily verify dealer-wise financial positions without maintaining separate Excel sheets.

---

# 8.11 Notification & Communication Module

The system shall support communication workflows for payment reminders and finance alerts.

### Key Requirements

1. Send WhatsApp payment reminders.
2. Send overdue payment alerts.
3. Send Credit Note and Debit Note alerts.
4. Send payment confirmation notifications.
5. Send finance escalation reminders.
6. Maintain notification history.
7. Allow configuration of message templates.
8. Allow manual and automated notification triggers.

### Expected Outcome

The system will improve dealer follow-up and reduce delayed payments.

---

# 8.12 MIS Reports & Dashboard Module

The MIS module shall provide management and finance reports.

### Required Reports

1. Dealer-wise sales report
2. Dealer-wise outstanding report
3. Overdue payment report
4. Payment aging report
5. Credit Note report
6. Debit Note report
7. Cash discount utilization report
8. Dealer ledger report
9. Product-wise sales report
10. Company-wise finance summary
11. Payment reconciliation report
12. Daily payment collection report
13. Monthly sales and finance summary
14. Pending approval report
15. Audit/activity report

### Dashboard Requirements

1. Total sales
2. Total outstanding
3. Total overdue amount
4. Total payment received
5. Total Credit Notes generated
6. Total Debit Notes generated
7. Top outstanding dealers
8. Overdue dealer count
9. Company-wise summary
10. Recent finance activities

### Export Requirements

1. Excel export
2. PDF export
3. Date range filters
4. Company-wise filters
5. Dealer-wise filters
6. Product-wise filters

### Expected Outcome

Management will get real-time visibility without depending on manual Excel reporting.

---

# 8.13 User & Role Management Module

The system shall provide secure user access control.

### Key Requirements

1. Create and manage users.
2. Assign users to company.
3. Assign roles and permissions.
4. Restrict access based on role and company.
5. Allow user activation/deactivation.
6. Maintain login and activity history.
7. Restrict sensitive finance operations to authorized users only.

### Expected Outcome

The system will protect sensitive financial data and maintain proper accountability.

---

# 8.14 Audit Log Module

The system shall maintain audit logs for important activities.

### Key Requirements

1. Track record creation, update, deletion, approval, and cancellation.
2. Track user, date, time, IP address, and changed values.
3. Maintain audit logs for dealer, product, pricing, order, payment, Credit Note, Debit Note, and configuration changes.
4. Allow admin users to view audit history.
5. Prevent unauthorized modification of audit records.

### Expected Outcome

The system will provide traceability and accountability for all important business actions.

---

# 8.15 Import & Export Module

The system shall support Excel-based import and export for selected modules.

### Key Requirements

1. Dealer import
2. Product import
3. Pricing import
4. Opening balance import
5. Payment import, if required
6. Validation before import
7. Error file generation for failed rows
8. Import history tracking
9. Excel export for reports and master data

### Expected Outcome

The system will support smooth migration from Excel and reduce manual data entry.

---

## 9. Business Rules

### 9.1 Company Rules

1. Every dealer, product, order, invoice, payment, and finance transaction must belong to a company.
2. Users can access only assigned company data unless they are Super Admin.
3. Reports must support company-wise filtering.

---

### 9.2 Dealer Rules

1. Dealer code or unique identifier should be generated by the system.
2. Duplicate GST/PAN/mobile number validation should be applied where applicable.
3. Inactive or blocked dealers should not be allowed for new orders.

---

### 9.3 Order Rules

1. Order amount shall be calculated based on product quantity and applicable rate.
2. Order quantity shall be maintained in MT.
3. Order status shall be updated through defined workflow.
4. Cancelled orders shall not affect finance calculations.

---

### 9.4 Payment Rules

1. Payment may be full or partial.
2. Payment shall be linked with dealer and invoice.
3. Outstanding amount shall update automatically after payment entry.
4. Payment date shall be used for discount and penalty calculation.
5. Payment entries should not be deleted after posting; reversal/adjustment should be used instead.

---

### 9.5 Cash Discount Rules

1. Discount shall be calculated based on payment timeline.
2. Discount shall be calculated per MT.
3. Eligible discount shall be adjusted through Credit Note.
4. Discount slabs shall be configurable.
5. Discount calculation shall be stored for future reference.

---

### 9.6 Debit Note Rules

1. Penalty shall apply after allowed due date.
2. Penalty shall be calculated as Rs. 30 per MT per day, unless changed by authorized configuration.
3. Debit Note shall be generated for overdue penalty.
4. Posted Debit Notes shall update dealer ledger and outstanding.

---

### 9.7 Credit Note Rules

1. Credit Note shall be generated for eligible discount benefits.
2. Credit Note shall be linked with dealer, invoice, payment, and company.
3. Posted Credit Notes shall update dealer ledger.
4. Cancelled Credit Notes shall maintain cancellation reason and audit history.

---

## 10. Non-Functional Requirements

### 10.1 Security

1. Secure login and authentication.
2. Role-based access control.
3. Company-wise data isolation.
4. Secure API architecture.
5. Password encryption.
6. Audit logs.
7. Restricted access to sensitive finance data.

---

### 10.2 Performance

1. Reports should load within acceptable time for normal business data volume.
2. Database indexing should be used for finance, dealer, order, and report tables.
3. Large reports should support filters before export.
4. System should be designed for future scalability.

---

### 10.3 Usability

1. User interface should be simple and business-friendly.
2. Finance screens should clearly show outstanding, payment, discount, and penalty values.
3. Statuses should be color-coded.
4. Reports should be easy to filter and export.
5. Important actions should show confirmation prompts.

---

### 10.4 Data Integrity

1. Posted finance transactions should not be directly edited.
2. Adjustments should be made through proper reversal or correction entries.
3. Master data changes should be logged.
4. Credit Note and Debit Note calculations should be traceable.

---

### 10.5 Backup & Recovery

1. Regular database backup should be planned.
2. Backup and restore strategy should be defined before production deployment.
3. Critical financial data should be protected from accidental loss.

---

## 11. Integration Requirements

### 11.1 WhatsApp Integration

The system shall support WhatsApp integration for:

1. Payment reminders
2. Overdue payment alerts
3. Credit Note alerts
4. Debit Note alerts
5. Payment confirmation notifications

Actual gateway provider, pricing, API availability, and message template approval shall be finalized separately.

---

### 11.2 Email Integration

Email integration may be considered for future communication workflows such as reports, alerts, and notifications.

---

### 11.3 Tally Integration — Future Scope

The system shall be designed in a way that future Tally integration can be added.

### Possible Tally Integration Scope

1. Invoice synchronization
2. Payment synchronization
3. Dealer ledger synchronization
4. Credit Note synchronization
5. Debit Note synchronization
6. GST and voucher entry support
7. Company-wise accounting mapping
8. Scheduled or real-time data synchronization

Tally integration will require separate technical analysis and API confirmation.

---

## 12. Data Migration Requirements

Since the current process is Excel-based, initial data migration will be required.

### Data Required from Client

1. Dealer master data
2. Product master data
3. Product pricing data
4. Existing outstanding data
5. Existing invoice data, if required
6. Existing payment records, if required
7. Existing Credit Note and Debit Note records, if required
8. Opening balances
9. Company-wise data separation details

### Data Migration Rules

1. Client shall provide clean and verified Excel data.
2. Development team shall provide standard Excel templates.
3. Imported data shall be validated before final upload.
4. Failed records shall be shared with error reasons.
5. Final migrated data shall be verified and approved by the client.

---

## 13. Approval Workflow (If required)

Approval workflow may be required for sensitive finance actions.

### Suggested Approval Items

1. Credit Note approval
2. Debit Note approval
3. Manual finance adjustment approval
4. Pricing change approval
5. Dealer credit limit change approval
6. Order approval, if required

Final approval levels shall be confirmed by the client.

---

## 14. Status & Color Coding

The system should use clear statuses and color coding for easy identification.

### Suggested Statuses

| Area         | Status Examples                                                        |
| ------------ | ---------------------------------------------------------------------- |
| Order        | Draft, Submitted, Approved, Invoiced, Dispatched, Completed, Cancelled |
| Payment      | Pending, Partial, Paid, Overdue                                        |
| Credit Note  | Draft, Pending Approval, Approved, Posted, Cancelled                   |
| Debit Note   | Draft, Pending Approval, Approved, Posted, Cancelled                   |
| Dealer       | Active, Inactive, Blocked                                              |
| Notification | Pending, Sent, Failed                                                  |

### Suggested Color Indications

| Status Type | Suggested Meaning                |
| ----------- | -------------------------------- |
| Green       | Completed / Paid / Approved      |
| Yellow      | Pending / Partial / Under Review |
| Red         | Overdue / Failed / Blocked       |
| Blue        | Draft / Informational            |
| Grey        | Cancelled / Inactive             |

---

## 15. Reports and Export Requirements

All key reports should support:

1. Company filter
2. Dealer filter
3. Date range filter
4. Product filter where applicable
5. Status filter
6. Excel export
7. PDF export where required
8. Summary and detailed view

---

## 16. Assumptions

1. The client will provide final finance rules before development.
2. Cash discount slabs and penalty rules may be configurable.
3. Two-company operation is required from the initial version.
4. Tally integration is future scope unless separately approved.
5. WhatsApp integration depends on third-party API provider availability.
6. Client will provide clean master data for import.
7. Final report formats will be approved before development.
8. User roles and permission structure will be finalized during system design.

---

## 17. Dependencies

The project depends on the following:

1. Final approval of BRD.
2. Finalization of finance calculation rules.
3. Finalization of Credit Note and Debit Note workflows.
4. Availability of master data from client.
5. Confirmation of two-company data structure.
6. Confirmation of required reports.
7. Confirmation of WhatsApp API provider.
8. Confirmation of Tally integration approach for future phase.
9. Timely feedback during UI and UAT stages.

---

## 18. Risks and Mitigation

| Risk                            | Impact                       | Mitigation                                   |
| ------------------------------- | ---------------------------- | -------------------------------------------- |
| Finance rules are not finalized | Calculation errors or rework | Final rule approval before development       |
| Excel data is inconsistent      | Migration errors             | Use standard templates and validation        |
| Users are used to Excel         | Adoption delay               | Provide simple UI and training               |
| Company data segregation issue  | Security risk                | Strict company-wise access control           |
| Frequent workflow changes       | Timeline impact              | Freeze scope after BRD approval              |
| Tally API uncertainty           | Future integration delay     | Keep Tally as separate future phase          |
| Large report data               | Performance issue            | Use filters, indexing, and optimized queries |

---

## 19. Acceptance Criteria

The system shall be considered accepted when:

1. Dealer records can be created, updated, searched, imported, and exported.
2. Product and pricing records can be managed company-wise.
3. Dealer orders can be created and tracked.
4. Invoice and payment details can be recorded.
5. Outstanding balance is calculated correctly.
6. Cash discount eligibility is calculated as per approved slabs.
7. Debit Note penalty is calculated correctly for overdue payments.
8. Credit Notes and Debit Notes are generated and tracked properly.
9. Dealer ledger shows accurate financial transactions.
10. MIS reports match approved business expectations.
11. Company-wise data segregation works correctly.
12. Role-based permissions work correctly.
13. Audit logs are maintained for critical actions.
14. Excel/PDF exports work for required reports.
15. UAT is completed and approved by the client.

---

## 20. Suggested Implementation Phases

### Phase 1: Requirement Finalization & UI Wireframes

1. Finalize BRD.
2. Finalize finance rules.
3. Finalize user roles and permissions.
4. Prepare UI wireframes.
5. Review and approve workflows with client.

---

### Phase 2: Master & User Management

1. Company management
2. User and role management
3. Dealer management
4. Product and pricing management
5. Excel import/export for master data

---

### Phase 3: Order & Invoice Management

1. Dealer order entry
2. Product/rate calculation
3. Order status tracking
4. Invoice mapping
5. Basic order reports

---

### Phase 4: Finance Automation

1. Payment entry
2. Outstanding calculation
3. Payment aging
4. Cash discount calculation
5. Debit Note penalty calculation
6. Dealer ledger

---

### Phase 5: Credit Note & Debit Note Management

1. Credit Note generation
2. Debit Note generation
3. Approval workflow, if required
4. Ledger posting
5. Credit/Debit Note reports

---

### Phase 6: MIS, Notification & Final Testing

1. MIS dashboards
2. Finance reports
3. WhatsApp notification integration
4. Export features
5. UAT testing
6. Bug fixing
7. Production deployment

---

### Phase 7: Future Enhancements

1. Tally API integration
2. Dealer portal
3. Payment gateway integration
4. Advanced analytics
5. Mobile app
6. Bank reconciliation

---

## 21. Open Points for Client Confirmation

The following points require client confirmation before development:

1. Final cash discount slab rules.
2. Exact due date calculation logic.
3. Whether discount is based on invoice date, payment date, due date, or credit period.
4. Whether partial payments are eligible for discount.
5. Whether Credit Note and Debit Note require approval before posting.
6. Final Credit Note and Debit Note numbering format.
7. Required invoice fields.
8. Required order approval workflow.
9. Dealer credit limit rules.
10. Opening balance migration format.
11. Required MIS report formats.
12. WhatsApp API provider preference.
13. Final user roles and permissions.
14. Tally integration timeline and scope.
15. Whether manual adjustments are allowed and who can approve them.

---

## 22. Final Conclusion

The proposed Dealer Management & Finance Automation System will provide a centralized, secure, and scalable platform for managing dealer operations and finance workflows.

The system will reduce Excel dependency, improve finance accuracy, automate discount and penalty calculations, streamline Credit Note and Debit Note processing, and provide real-time MIS visibility to management.

The most critical success factor for this project will be the finalization of finance rules, especially payment timeline, cash discount eligibility, overdue penalty calculation, Credit Note logic, Debit Note logic, and approval workflow.

Once implemented successfully, the system will improve operational efficiency, accounting transparency, reconciliation speed, and management decision-making.

---

# Approval

| Name | Designation | Signature | Date |
| ---- | ----------- | --------- | ---- |
|      |             |           |      |
|      |             |           |      |
|      |             |           |      |
