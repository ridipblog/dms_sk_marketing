<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\UserRoleCompany;
use App\Models\UploadTrack;
use App\Models\Accounts\Invoice;
use App\Models\Accounts\InvoicePayment;
use App\Models\Accounts\PaymentTrack;
use App\Models\Accounts\VoucherType;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductPricing;
use App\Jobs\ProcessAccountsInvoiceUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvoiceUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminRole;
    protected $company;
    protected $activeMapping;
    protected $dealer;
    protected $dealerCompany;
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

        // 6. Setup Dealer
        $this->dealer = Dealer::create([
            'dealer_name' => 'Dealer Test',
            'pan_number' => 'ABCDE1234F',
            'email' => 'dealer@example.com',
            'phone' => '9876543210',
            'address' => 'Mumbai',
            'dealer_code' => 'DL202',
            'gst_number' => '27ABCDE1234F1Z1',
            'status' => 'active'
        ]);

        $this->dealerCompany = DealerCompany::create([
            'dealer_id' => $this->dealer->id,
            'company_id' => $this->company->id,
            'opening_balance' => 0.00,
            'created_by' => $this->adminUser->id,
            'status' => 'active'
        ]);

        // 7. Setup Category & Product in Inventory
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
            'hsn_code' => 'PRD-001',
            'unit' => 'MT',
            'base_price' => 50000.00,
            'stock_quantity' => 10.000,
            'status' => 'active'
        ]);

        ProductPricing::create([
            'product_id' => $this->product->id,
            'price_per_mt' => 4000.00,
            'gst_percentage' => 18,
            'effective_from' => '2026-07-08',
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

    public function test_download_invoice_template_contains_manual_upload(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('accounts.upload.invoices.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=invoice_upload_template.csv');

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        
        $this->assertGreaterThan(0, count($lines));
        $header = str_getcsv($lines[0]);
        
        // Assert manual_upload is the 6th column
        $this->assertEquals('manual_upload', $header[5]);
        
        // Assert demo rows contain manual_upload value
        $firstDemoRow = str_getcsv($lines[1]);
        $this->assertEquals('0', $firstDemoRow[5]);

        $manualDemoRow = str_getcsv($lines[4]);
        $this->assertEquals('1', $manualDemoRow[5]);
        $this->assertEquals('50000.00', $manualDemoRow[4]);
    }

    public function test_process_invoice_upload_job_standard_flow(): void
    {
        Storage::fake('local');
        $this->actAsAdmin();

        $content = "Invoice No,Buyer Code,Invoice Date,Product Code,Quantity,manual_upload\n";
        $content .= ",DL202,2026-07-16,PRD-001,10.00,0\n";

        $filePath = 'accounts_uploads/' . time() . '_invoice_valid.csv';
        Storage::disk('local')->put($filePath, $content);

        $track = UploadTrack::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_user_company_id' => $this->activeMapping->id,
            'file_name' => 'invoice_valid.csv',
            'status' => 'pending',
            'upload_type' => 'accounts_invoices',
        ]);

        $job = new ProcessAccountsInvoiceUpload($track->id, $filePath);
        $job->handle();

        $track->refresh();

        $this->assertEquals('completed', $track->status);
        $this->assertEquals(1, $track->imported_rows);
        $this->assertEquals(0, $track->failed_rows);

        // Verify draft invoice creation
        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(0, $invoice->invoice_status);
        $this->assertEquals(10.00, $invoice->total_quantity);
        $this->assertEquals(47200.00, $invoice->chargeable_amount);
    }

    public function test_process_invoice_upload_job_manual_upload_flow(): void
    {
        Storage::fake('local');
        $this->actAsAdmin();

        // manual_upload = 1, debit column = 50000.00
        $content = "Invoice No,Buyer Code,Invoice Date,Product Code,Quantity,manual_upload,credit,debit\n";
        $content .= "INV-202606-0000015,DL202,2026-07-16,,0.00,1,,50000.00\n";

        $filePath = 'accounts_uploads/' . time() . '_invoice_manual.csv';
        Storage::disk('local')->put($filePath, $content);

        $track = UploadTrack::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_user_company_id' => $this->activeMapping->id,
            'file_name' => 'invoice_manual.csv',
            'status' => 'pending',
            'upload_type' => 'accounts_invoices',
        ]);

        $job = new ProcessAccountsInvoiceUpload($track->id, $filePath);
        $job->handle();

        $track->refresh();

        $this->assertEquals('completed', $track->status);
        $this->assertEquals(1, $track->imported_rows);
        $this->assertEquals(0, $track->failed_rows);

        // Verify invoice was processed exactly as requested
        $invoice = Invoice::where('invoice_no', 'INV-202606-0000015')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($this->dealerCompany->id, $invoice->buyer_id);
        $this->assertEquals($this->dealerCompany->id, $invoice->ship_to);
        $this->assertEquals($this->activeMapping->id, $invoice->created_by);
        $this->assertNull($invoice->total_quantity);
        $this->assertNull($invoice->total_amount);
        $this->assertEquals(50000.00, $invoice->chargeable_amount);
        $this->assertNull($invoice->total_gst_amount);
        $this->assertNull($invoice->total_cgst_amount);
        $this->assertNull($invoice->total_sgst_amount);
        $this->assertEquals(9.00, $invoice->cgst);
        $this->assertEquals(9.00, $invoice->sgst);
        $this->assertEquals(18.00, $invoice->gst);
        $this->assertEquals('2026-04-01', $invoice->invoice_generate_date);
        $this->assertEquals('2026-04-22', $invoice->due_date);
        $this->assertNull($invoice->no_of_goods);
        $this->assertNull($invoice->round_of);
        $this->assertEquals(1, $invoice->invoice_status);
        $this->assertEquals(3, $invoice->whatsapp_reminder_stage);
        $this->assertEquals(1, $invoice->manual_amount_update);

        // Verify InvoiceDetail is created for debit flow
        $detail = \App\Models\Accounts\InvoiceDetail::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals(50000.00, $detail->chargeable_amount);

        // Verify InvoicePayment is created
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(50000.00, $payment->outstanding_amount);

        // Verify PaymentTrack is created
        $paymentTrack = PaymentTrack::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($paymentTrack);
        $this->assertEquals(50000.00, $paymentTrack->amount);
        $this->assertEquals(50000.00, $paymentTrack->balance_amount);
        $this->assertEquals('entry', $paymentTrack->payment_mode);
        $this->assertEquals('2026-03-31', $paymentTrack->transaction_date->format('Y-m-d'));
        $this->assertEquals('2026-04-01', $paymentTrack->created_at->format('Y-m-d'));
    }

    public function test_process_invoice_upload_job_manual_upload_credit_flow(): void
    {
        Storage::fake('local');
        $this->actAsAdmin();

        // manual_upload = 1, credit = 30000.00, debit = empty
        $content = "Invoice No,Buyer Code,Invoice Date,Product Code,Quantity,manual_upload,credit,debit\n";
        $content .= "INV-202606-0000016,DL202,2026-07-16,,0.00,1,30000.00,\n";

        $filePath = 'accounts_uploads/' . time() . '_invoice_manual_credit.csv';
        Storage::disk('local')->put($filePath, $content);

        $track = UploadTrack::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_user_company_id' => $this->activeMapping->id,
            'file_name' => 'invoice_manual_credit.csv',
            'status' => 'pending',
            'upload_type' => 'accounts_invoices',
        ]);

        $job = new ProcessAccountsInvoiceUpload($track->id, $filePath);
        $job->handle();

        $track->refresh();

        $this->assertEquals('completed', $track->status);
        $this->assertEquals(1, $track->imported_rows);
        $this->assertEquals(0, $track->failed_rows);

        // Verify invoice was processed: chargeable_amount = 0
        $invoice = Invoice::where('invoice_no', 'INV-202606-0000016')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(0.00, $invoice->chargeable_amount);

        // Verify InvoiceDetail is created with 0 value
        $detail = \App\Models\Accounts\InvoiceDetail::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals(0.00, $detail->chargeable_amount);

        // Verify InvoicePayment: outstanding_amount = 0, paid_amount = 30000.00
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(0.00, $payment->outstanding_amount);
        $this->assertEquals(30000.00, $payment->paid_amount);

        // Verify PaymentTrack: amount = 30000.00, balance_amount = 0.00, voucher_type_id = 1
        $paymentTrack = PaymentTrack::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($paymentTrack);
        $this->assertEquals(30000.00, $paymentTrack->amount);
        $this->assertEquals(0.00, $paymentTrack->balance_amount);
        $this->assertEquals(1, $paymentTrack->voucher_type_id);
        $this->assertEquals('entry', $paymentTrack->payment_mode);
        $this->assertEquals('2026-03-31', $paymentTrack->transaction_date->format('Y-m-d'));
        $this->assertEquals('2026-04-01', $paymentTrack->created_at->format('Y-m-d'));
    }

    public function test_process_invoice_upload_job_manual_upload_fails_without_credit_or_debit(): void
    {
        Storage::fake('local');
        $this->actAsAdmin();

        // manual_upload = 1, credit = empty, debit = empty
        $content = "Invoice No,Buyer Code,Invoice Date,Product Code,Quantity,manual_upload,credit,debit\n";
        $content .= "INV-202606-0000017,DL202,2026-07-16,,0.00,1,,\n";

        $filePath = 'accounts_uploads/' . time() . '_invoice_manual_fail.csv';
        Storage::disk('local')->put($filePath, $content);

        $track = UploadTrack::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_user_company_id' => $this->activeMapping->id,
            'file_name' => 'invoice_manual_fail.csv',
            'status' => 'pending',
            'upload_type' => 'accounts_invoices',
        ]);

        $job = new ProcessAccountsInvoiceUpload($track->id, $filePath);
        $job->handle();

        $track->refresh();

        $this->assertEquals('completed', $track->status); // job completes even with row errors
        $this->assertEquals(0, $track->imported_rows);
        $this->assertEquals(1, $track->failed_rows);
        $this->assertStringContainsString('Manual upload row must specify either a credit or a debit amount', $track->error_log);
    }
}
