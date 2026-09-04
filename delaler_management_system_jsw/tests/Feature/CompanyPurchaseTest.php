<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\UserRoleCompany;
use App\Models\Inventory\Product;
use App\Models\Inventory\Category;
use App\Models\Inventory\ProductPricing;
use App\Models\Purchase\Supplier;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceDetail;
use App\Models\Purchase\PurchaseInvoicePayment;
use App\Models\Purchase\PurchasePaymentTrack;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoiceDetail;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminRole;
    protected $company;
    protected $activeMapping;
    protected $category;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        Role::create(['role_name' => 'Super Admin', 'priority' => 1, 'status' => 'active']);
        $this->adminRole = Role::create(['role_name' => 'Admin Users', 'priority' => 2, 'status' => 'active']);

        // 2. Setup Voucher Types
        VoucherType::create(['name' => 'SALES', 'description' => 'Sales invoice entry']);
        VoucherType::create(['name' => 'PURCHASE', 'description' => 'Purchase entry']);
        VoucherType::create(['name' => 'PAYMENT', 'description' => 'Payment entry']);

        // 3. Setup Company
        $this->company = Company::create([
            'company_code' => 'COMP01',
            'company_name' => 'JSW Steel',
            'status' => 'active',
        ]);

        // 4. Setup User
        $this->adminUser = User::factory()->create([
            'phone' => '9876543210',
            'password' => Hash::make('password123'),
        ]);

        // 5. Setup Active Mapping
        $this->activeMapping = UserRoleCompany::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_id' => $this->adminRole->id,
        ]);

        // 6. Setup Category & Product in Inventory
        $this->category = Category::create([
            'company_id' => $this->company->id,
            'category_name' => 'Steel Plates',
            'category_code' => 'SP01',
            'status' => 'active'
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'product_name' => 'Mild Steel Plate 10mm',
            'sku_code' => 'MSP-10MM',
            'unit' => 'MT',
            'base_price' => 50000.00,
            'stock_quantity' => 10.000, // Initial stock
            'status' => 'active'
        ]);
    }

    /**
     * Authenticate session.
     */
    protected function actAsAdmin()
    {
        $this->actingAs($this->adminUser);
        session([
            'active_map_id' => $this->activeMapping->id,
            'active_company_id' => $this->company->id,
            'active_company_name' => $this->company->company_name,
            'active_role_id' => $this->adminRole->id,
            'active_role_name' => $this->adminRole->role_name,
        ]);
    }

    public function test_supplier_crud(): void
    {
        $this->actAsAdmin();

        // 1. Create Supplier
        $response = $this->postJson(route('purchase.suppliers.store'), [
            'name' => 'Tata Steel Vendor',
            'gstin' => '22AAAAA0000A1Z5',
            'phone' => '9999999999',
            'email' => 'tata@example.com',
            'address' => 'Jamshedpur Factory'
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('suppliers', ['name' => 'Tata Steel Vendor']);

        // Try to create duplicate supplier with same GSTIN
        $dupGstResponse = $this->postJson(route('purchase.suppliers.store'), [
            'name' => 'Duplicate Tata Steel Vendor',
            'gstin' => '22AAAAA0000A1Z5',
            'phone' => '7777777777',
            'email' => 'dup@example.com',
            'address' => 'Jamshedpur Factory'
        ]);
        $dupGstResponse->assertStatus(200)->assertJson(['success' => false]);

        // Try to create duplicate supplier with same Phone
        $dupPhoneResponse = $this->postJson(route('purchase.suppliers.store'), [
            'name' => 'Duplicate Tata Steel Vendor 2',
            'gstin' => '22AAAAA0000A1Z6',
            'phone' => '9999999999',
            'email' => 'dup2@example.com',
            'address' => 'Jamshedpur Factory'
        ]);
        $dupPhoneResponse->assertStatus(200)->assertJson(['success' => false]);

        // Assert that multiple suppliers with empty string GSTIN/Phone are allowed (treated as null)
        $emptyResponse1 = $this->postJson(route('purchase.suppliers.store'), [
            'name' => 'Empty Supplier 1',
            'gstin' => '',
            'phone' => '',
            'email' => 'empty1@example.com',
            'address' => 'Factory 1'
        ]);
        $emptyResponse1->assertStatus(200)->assertJson(['success' => true]);

        $emptyResponse2 = $this->postJson(route('purchase.suppliers.store'), [
            'name' => 'Empty Supplier 2',
            'gstin' => '  ',
            'phone' => '',
            'email' => 'empty2@example.com',
            'address' => 'Factory 2'
        ]);
        $emptyResponse2->assertStatus(200)->assertJson(['success' => true]);

        // 2. Edit & Update Supplier
        $supplier = Supplier::first();
        $encId = Crypt::encryptString($supplier->id);

        $editResponse = $this->getJson(route('purchase.suppliers.edit', $encId));
        $editResponse->assertStatus(200)->assertJson(['success' => true]);

        $updateResponse = $this->postJson(route('purchase.suppliers.update', $encId), [
            'name' => 'Tata Steel Vendor Updated',
            'gstin' => '22AAAAA0000A1Z5',
            'phone' => '8888888888',
            'email' => 'tata_updated@example.com',
            'address' => 'Jamshedpur Factory Main Gate',
            'status' => 1
        ]);
        $updateResponse->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('suppliers', ['name' => 'Tata Steel Vendor Updated', 'phone' => '8888888888']);
    }

    public function test_purchase_invoice_flow(): void
    {
        $this->actAsAdmin();

        $supplier = Supplier::create([
            'name' => 'Jindal Suppliers',
            'status' => 1
        ]);

        // 1. Create Purchase Draft
        $response = $this->postJson(route('purchase.invoices.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-08',
            'due_date' => '2026-07-30',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        
        $purchase = PurchaseInvoice::first();
        $this->assertNotNull($purchase);
        $this->assertEquals(0, $purchase->status); // Draft status

        $encPurchaseId = Crypt::encryptString($purchase->id);

        // 2. Add Line Item (Product) to Draft
        $itemResponse = $this->postJson(route('purchase.invoices.store_item'), [
            'purchase_id' => $encPurchaseId,
            'product_id' => $this->product->id,
            'quantity' => 5.000,
            'rate' => 45000.00,
            'gst_percentage' => 18.00
        ]);
        $itemResponse->assertStatus(200)->assertJson(['success' => true]);

        // Add second Line Item with custom 5% GST percentage
        $itemResponse2 = $this->postJson(route('purchase.invoices.store_item'), [
            'purchase_id' => $encPurchaseId,
            'product_id' => $this->product->id,
            'quantity' => 2.000,
            'rate' => 10000.00,
            'gst_percentage' => 5.00
        ]);
        $itemResponse2->assertStatus(200)->assertJson(['success' => true]);

        $purchase->refresh();
        $this->assertEquals(7.000, $purchase->total_quantity);
        $this->assertEquals(245000.00, $purchase->total_amount); // 225000 + 20000
        $this->assertEquals(41500.00, $purchase->total_gst_amount); // 40500 + 1000
        $this->assertEquals(286500.00, $purchase->chargeable_amount); // 245000 + 41500

        // 3. Finalize Purchase Bill
        $finalizeResponse = $this->postJson(route('purchase.invoices.finalize'), [
            'purchase_id' => $encPurchaseId
        ]);
        $finalizeResponse->assertStatus(200)->assertJson(['success' => true]);

        $purchase->refresh();
        $this->assertEquals(1, $purchase->status); // Finalized status

        // 4. Verify Stock levels INCREASED
        $this->product->refresh();
        $this->assertEquals(17.000, $this->product->stock_quantity); // 10.000 + 7.000

        $purchaseVoucher = VoucherType::where('name', 'PURCHASE')->first();
        // 5. Verify Purchase Voucher recorded in purchase_payment_tracks
        $this->assertDatabaseHas('purchase_payment_tracks', [
            'purchase_invoice_id' => $purchase->id,
            'amount' => 286500.00,
            'balance_amount' => 286500.00,
            'voucher_type_id' => $purchaseVoucher->id,
            'payment_mode' => 'entry'
        ]);

        // 6. Verify outstanding balance initialized
        $this->assertDatabaseHas('purchase_invoice_payments', [
            'purchase_invoice_id' => $purchase->id,
            'outstanding_amount' => 286500.00,
            'paid_amount' => 0.00,
            'clear_status' => 0
        ]);

        // 7. Record supplier partial repayment
        $paymentResponse = $this->postJson(route('purchase.invoices.payment'), [
            'purchase_id' => $encPurchaseId,
            'amount' => 100000.00,
            'payment_mode' => 'bank_transfer',
            'transaction_date' => '2026-07-08',
            'transaction_id' => 'TXN-REPAY001',
            'remarks' => 'First partial payment'
        ]);
        $paymentResponse->assertStatus(200)->assertJson(['success' => true]);

        // Verify running balances adjusted
        $this->assertDatabaseHas('purchase_invoice_payments', [
            'purchase_invoice_id' => $purchase->id,
            'outstanding_amount' => 186500.00, // 286500 - 100000
            'paid_amount' => 100000.00,
            'clear_status' => 0
        ]);

        $paymentVoucher = VoucherType::where('name', 'PAYMENT')->first();
        // Verify PAYMENT voucher recorded in purchase_payment_tracks
        $this->assertDatabaseHas('purchase_payment_tracks', [
            'purchase_invoice_id' => $purchase->id,
            'amount' => 100000.00,
            'balance_amount' => 186500.00,
            'voucher_type_id' => $paymentVoucher->id,
            'payment_mode' => 'bank_transfer'
        ]);
    }

    public function test_sales_invoice_decreases_stock(): void
    {
        $this->actAsAdmin();

        // Register Dealer details
        $dealer = Dealer::create([
            'dealer_name' => 'Super Dealer LLC',
            'pan_number' => 'ABCDE1234F',
            'dealer_code' => 'DL-001',
            'status' => 'active'
        ]);

        $dealerCompany = DealerCompany::create([
            'dealer_id' => $dealer->id,
            'company_id' => $this->company->id,
            'opening_balance' => 0.00,
            'created_by' => $this->adminUser->id,
            'status' => 'active'
        ]);

        // Setup product pricing
        $pricing = ProductPricing::create([
            'product_id' => $this->product->id,
            'price_per_mt' => 60000.00,
            'gst_percentage' => 18.00,
            'effective_from' => '2026-07-08',
            'status' => 'active'
        ]);

        // 1. Create Sales Invoice
        $invoice = Invoice::create([
            'invoice_no' => 'INV-202607-001',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'gst' => 18.00,
            'cgst' => 9.00,
            'sgst' => 9.00,
            'total_quantity' => 4.000,
            'total_amount' => 240000.00,
            'total_gst_amount' => 43200.00,
            'total_cgst_amount' => 21600.00,
            'total_sgst_amount' => 21600.00,
            'chargeable_amount' => 283200.00,
            'invoice_status' => 0, // draft
            'created_by' => $this->activeMapping->id
        ]);

        // 2. Add Invoice Line Item
        $invoiceDetail = InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'product_pricing_id' => $pricing->id,
            'quantity' => 4.000, // MT quantity
            'total_amount' => 240000.00,
            'cgst_amount' => 21600.00,
            'sgst_amount' => 21600.00,
            'gst_amount' => 43200.00,
            'chargeable_amount' => 283200.00
        ]);

        // 3. Finalize Sales Invoice (triggers stock decrement)
        $finalizeResponse = $this->postJson(route('accounts.invoices.finalize'), [
            'invoice_id' => Crypt::encryptString($invoice->id)
        ]);
        $finalizeResponse->assertStatus(200)->assertJson(['success' => true]);

        // 4. Verify Stock levels DECREASED
        $this->product->refresh();
        $this->assertEquals(6.000, $this->product->stock_quantity); // 10.000 - 4.000
    }

    public function test_invoice_view_displays_company_bank_details(): void
    {
        $this->actAsAdmin();

        // Create company bank details
        \App\Models\CompanyBankDetail::create([
            'company_id' => $this->company->id,
            'account_holder_name' => 'JSW Steel Ltd Bank Account',
            'bank_name' => 'State Bank of India',
            'account_no' => '1234567890',
            'ifsc_code' => 'SBIN0000001',
            'branch_name' => 'Guwahati',
            'swift_code' => 'SBININBBXXX',
            'status' => 'active'
        ]);

        // Create Sales Invoice
        $dealer = Dealer::create([
            'dealer_name' => 'Dealer Test Bank Details',
            'pan_number' => 'ABCDE1234F',
            'email' => 'dealerbank@example.com',
            'phone' => '8888888888',
            'address' => 'Assam',
            'dealer_code' => 'DL101',
            'gst_number' => '18ABCDE1234F1Z1',
            'status' => 'active'
        ]);

        $dealerCompany = DealerCompany::create([
            'dealer_id' => $dealer->id,
            'company_id' => $this->company->id,
            'opening_balance' => 0.00,
            'created_by' => $this->adminUser->id,
            'status' => 'active'
        ]);

        $invoice = Invoice::create([
            'invoice_no' => 'INV-BANK-TEST',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'gst' => 18.00,
            'cgst' => 9.00,
            'sgst' => 9.00,
            'total_quantity' => 1.000,
            'total_amount' => 60000.00,
            'total_gst_amount' => 10800.00,
            'total_cgst_amount' => 5400.00,
            'total_sgst_amount' => 5400.00,
            'chargeable_amount' => 70800.00,
            'invoice_status' => 0,
            'created_by' => $this->activeMapping->id
        ]);

        $encryptedId = Crypt::encryptString($invoice->id);
        $response = $this->get(route('accounts.invoices.view', ['id' => $encryptedId]));

        $response->assertStatus(200);
        $response->assertSee('JSW Steel Ltd Bank Account');
        $response->assertSee('State Bank of India');
        $response->assertSee('1234567890');
        $response->assertSee('SBIN0000001');
        $response->assertSee('(Guwahati)');
        $response->assertSee('SBININBBXXX');
    }

    public function test_send_overdue_whatsapp_command_staged(): void
    {
        $this->actAsAdmin();

        // 1. Configure Twilio config settings
        config([
            'services.twilio.sid' => 'mock_sid',
            'services.twilio.token' => 'mock_token',
            'services.twilio.from' => 'whatsapp:+14155238886'
        ]);

        // 2. Mock Twilio SDK Client
        $messagesMock = \Mockery::mock();
        $messagesMock->shouldReceive('create')
            ->times(3) // 3 reminders total
            ->withAnyArgs()
            ->andReturn(true);

        $twilioClientMock = \Mockery::mock(\Twilio\Rest\Client::class);
        $twilioClientMock->messages = $messagesMock;
        $twilioClientMock->shouldReceive('getAccountSid')->andReturn('mock_sid');

        // Bind mock to the service container
        $this->app->instance(\Twilio\Rest\Client::class, $twilioClientMock);

        // 3. Setup Dealer
        $dealer = Dealer::create([
            'dealer_name' => 'Dealer Whatsapp Test',
            'pan_number' => 'ABCDE1234F',
            'email' => 'dealerwhatsapp@example.com',
            'phone' => '9876543210',
            'address' => 'Guwahati',
            'dealer_code' => 'DL202',
            'gst_number' => '18ABCDE1234F1Z1',
            'status' => 'active'
        ]);

        $dealerCompany = DealerCompany::create([
            'dealer_id' => $dealer->id,
            'company_id' => $this->company->id,
            'opening_balance' => 0.00,
            'created_by' => $this->adminUser->id,
            'status' => 'active'
        ]);

        // 4. Create 3 Invoices with different overdue days
        $today = \Carbon\Carbon::today();

        // Invoice 1: 1 day overdue (due yesterday)
        $invoice1 = Invoice::create([
            'invoice_no' => 'INV-OVERDUE-1',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'due_date' => $today->copy()->subDays(1)->toDateString(),
            'invoice_status' => 1, // generated
            'created_by' => $this->activeMapping->id
        ]);
        \App\Models\Accounts\InvoicePayment::create([
            'invoice_id' => $invoice1->id,
            'outstanding_amount' => 5000.00,
            'clear_status' => 'pending payment'
        ]);

        // Invoice 2: 4 days overdue (due 4 days ago)
        $invoice2 = Invoice::create([
            'invoice_no' => 'INV-OVERDUE-4',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'due_date' => $today->copy()->subDays(4)->toDateString(),
            'invoice_status' => 1, // generated
            'created_by' => $this->activeMapping->id
        ]);
        \App\Models\Accounts\InvoicePayment::create([
            'invoice_id' => $invoice2->id,
            'outstanding_amount' => 10000.00,
            'clear_status' => 'pending payment'
        ]);

        // Invoice 3: 11 days overdue (due 11 days ago)
        $invoice3 = Invoice::create([
            'invoice_no' => 'INV-OVERDUE-11',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'due_date' => $today->copy()->subDays(11)->toDateString(),
            'invoice_status' => 1, // generated
            'created_by' => $this->activeMapping->id
        ]);
        \App\Models\Accounts\InvoicePayment::create([
            'invoice_id' => $invoice3->id,
            'outstanding_amount' => 15000.00,
            'clear_status' => 'pending payment'
        ]);

        // Invoice 4: 5 days overdue but manual_amount_update = 1 (should be skipped)
        $invoice4 = Invoice::create([
            'invoice_no' => 'INV-OVERDUE-MANUAL',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'due_date' => $today->copy()->subDays(5)->toDateString(),
            'invoice_status' => 1, // generated
            'manual_amount_update' => 1,
            'created_by' => $this->activeMapping->id
        ]);
        \App\Models\Accounts\InvoicePayment::create([
            'invoice_id' => $invoice4->id,
            'outstanding_amount' => 8000.00,
            'clear_status' => 'pending payment'
        ]);

        // 5. Run the Artisan command
        $this->artisan('invoices:send-overdue-whatsapp')
            ->assertExitCode(0);

        // 6. Assert database has been updated with the correct stages
        $invoice1->refresh();
        $invoice2->refresh();
        $invoice3->refresh();
        $invoice4->refresh();

        $this->assertEquals(1, $invoice1->whatsapp_reminder_stage);
        $this->assertEquals(2, $invoice2->whatsapp_reminder_stage);
        $this->assertEquals(3, $invoice3->whatsapp_reminder_stage);
        $this->assertEquals(0, $invoice4->whatsapp_reminder_stage);
    }

    public function test_sales_invoice_stock_validation(): void
    {
        $this->actAsAdmin();

        $dealer = Dealer::create([
            'dealer_name' => 'Stock Test Dealer',
            'pan_number' => 'ABCDE5678F',
            'dealer_code' => 'DL-999',
            'status' => 'active'
        ]);

        $dealerCompany = DealerCompany::create([
            'dealer_id' => $dealer->id,
            'company_id' => $this->company->id,
            'opening_balance' => 0.00,
            'created_by' => $this->adminUser->id,
            'status' => 'active'
        ]);

        $pricing = ProductPricing::create([
            'product_id' => $this->product->id,
            'price_per_mt' => 5000.00,
            'gst_percentage' => 18.00,
            'effective_from' => '2026-07-08',
            'status' => 'active'
        ]);

        // Initial product stock is 10.000 (from setUp)
        $invoice = Invoice::create([
            'invoice_no' => 'INV-STOCK-TEST-001',
            'buyer_id' => $dealerCompany->id,
            'ship_to' => $dealerCompany->id,
            'gst' => 18.00,
            'cgst' => 9.00,
            'sgst' => 9.00,
            'invoice_status' => 0,
            'created_by' => $this->activeMapping->id
        ]);

        // 1. Try to add item with quantity > stock (12.000 > 10.000)
        $response = $this->postJson(route('accounts.invoices.store_item'), [
            'invoice_id' => Crypt::encryptString($invoice->id),
            'product_pricing_id' => $pricing->id,
            'quantity' => 12.000,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Stock is low. Available stock: 10.000 MT.'
        ]);

        // 2. Add item with quantity <= stock (5.000 <= 10.000) -> should succeed
        $response2 = $this->postJson(route('accounts.invoices.store_item'), [
            'invoice_id' => Crypt::encryptString($invoice->id),
            'product_pricing_id' => $pricing->id,
            'quantity' => 5.000,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
        ]);

        // Get the added detail ID
        $detail = InvoiceDetail::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($detail);

        // 3. Try to edit the existing item to a quantity > stock (15.000) -> should fail
        $response4 = $this->postJson(route('accounts.invoices.store_item'), [
            'invoice_id' => Crypt::encryptString($invoice->id),
            'invoice_detail_id' => Crypt::encryptString($detail->id),
            'product_pricing_id' => $pricing->id,
            'quantity' => 15.000,
        ]);

        $response4->assertStatus(200);
        $response4->assertJson([
            'success' => false,
            'message' => 'Stock is low. Available stock: 10.000 MT.'
        ]);

        // 4. Manually reduce database stock below the invoice detail quantity to simulate concurrent changes
        $this->product->stock_quantity = 3.000;
        $this->product->save();

        // 5. Try to finalize the invoice (which requires 5.000, but only 3.000 is available) -> should fail
        $finalizeResponse = $this->postJson(route('accounts.invoices.finalize'), [
            'invoice_id' => Crypt::encryptString($invoice->id)
        ]);

        $finalizeResponse->assertStatus(200);
        $finalizeResponse->assertJson([
            'success' => false,
            'message' => 'Stock is low for product: ' . $this->product->product_name . '. Available stock: 3.000 MT.'
        ]);
    }
}
