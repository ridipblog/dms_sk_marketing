<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\UserRoleCompany;
use App\Models\UploadTrack;
use App\Jobs\ProcessUserUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminRole;
    protected $company;
    protected $activeMapping;

    protected function setUp(): void
    {
        parent::setUp();

        // Create initial roles
        Role::create(['role_name' => 'Super Admin', 'priority' => 1, 'status' => 'active']);
        $this->adminRole = Role::create(['role_name' => 'Admin Users', 'priority' => 2, 'status' => 'active']);
        Role::create(['role_name' => 'Branch Manager (BM)', 'priority' => 3, 'status' => 'active']);
        Role::create(['role_name' => 'Area Sales Manager (ASM)', 'priority' => 4, 'status' => 'active']);

        // Create company
        $this->company = Company::create([
            'company_code' => 'COMP01',
            'company_name' => 'JSW Steel',
            'status' => 'active',
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'phone' => '9876543210',
            'password' => Hash::make('password123'),
        ]);

        // Assign Admin Users role to this user
        $this->activeMapping = UserRoleCompany::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_id' => $this->adminRole->id,
        ]);
    }

    /**
     * Set active context in session for testing.
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

    public function test_user_upload_page_is_protected(): void
    {
        $response = $this->get(route('users.uploads.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_upload_controller_dispatches_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $this->actAsAdmin();

        // Create a dummy template file
        $content = "Name,Phone,Email,Designation,Status,Company Code,Role Name\n";
        $content .= "New User,1111111111,newuser@example.com,BM,Active,COMP01,Branch Manager (BM)\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', $content);

        $response = $this->postJson(route('users.uploads.import'), [
            'excel_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $track = UploadTrack::where('upload_type', 'users')->first();
        $this->assertNotNull($track);
        $this->assertEquals('pending', $track->status);
        $this->assertEquals('users.csv', $track->file_name);

        Queue::assertPushed(ProcessUserUpload::class, function ($job) use ($track) {
            return $job->timeout === 3600;
        });
    }

    public function test_user_upload_job_processes_valid_data(): void
    {
        Storage::fake('local');

        $this->actAsAdmin();

        // Setup import data
        $content = "Name,Phone,Email,Designation,Status,Company Code,Role Name\n";
        $content .= "Alice,2222222222,alice@example.com,Officer,Active,COMP01,Branch Manager (BM)\n";

        // Store file in private local disk
        $filePath = 'user_uploads/' . time() . '_users_valid.csv';
        Storage::disk('local')->put($filePath, $content);

        $track = UploadTrack::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_user_company_id' => $this->activeMapping->id,
            'file_name' => 'users_valid.csv',
            'status' => 'pending',
            'upload_type' => 'users',
        ]);

        // Execute job synchronously
        $job = new ProcessUserUpload($track->id, $filePath);
        $job->handle();

        // Refresh track
        $track->refresh();

        $this->assertEquals('completed', $track->status);
        $this->assertEquals(1, $track->imported_rows);
        $this->assertEquals(0, $track->failed_rows);
        $this->assertNull($track->error_file_path);

        // Verify user and mappings are created in DB
        $user = User::where('phone', '2222222222')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Alice', $user->name);
        $this->assertEquals('alice@example.com', $user->email);
        $this->assertEquals('Officer', $user->designation);

        $mapping = UserRoleCompany::where('user_id', $user->id)
            ->where('company_id', $this->company->id)
            ->first();
        $this->assertNotNull($mapping);
        $this->assertEquals('Branch Manager (BM)', $mapping->role->role_name);

        // Check file cleanup
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_user_upload_job_records_validation_and_priority_failures(): void
    {
        Storage::fake('local');

        $this->actAsAdmin();

        // Row 1 fails: Admin Users (priority 2) tries to assign Super Admin (priority 1 <= 2)
        // Row 2 fails: Invalid Email address
        // Row 3 fails: Company Code does not exist
        $content = "Name,Phone,Email,Designation,Status,Company Code,Role Name\n";
        $content .= "Bob,3333333333,bob@example.com,Director,Active,COMP01,Super Admin\n";
        $content .= "Charlie,4444444444,bad-email,Exec,Active,COMP01,Branch Manager (BM)\n";
        $content .= "David,5555555555,david@example.com,Manager,Active,NON_EXISTENT,Branch Manager (BM)\n";

        $filePath = 'user_uploads/' . time() . '_users_invalid.csv';
        Storage::disk('local')->put($filePath, $content);

        $track = UploadTrack::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role_user_company_id' => $this->activeMapping->id,
            'file_name' => 'users_invalid.csv',
            'status' => 'pending',
            'upload_type' => 'users',
        ]);

        $job = new ProcessUserUpload($track->id, $filePath);
        $job->handle();

        $track->refresh();

        $this->assertEquals('completed', $track->status);
        $this->assertEquals(0, $track->imported_rows);
        $this->assertEquals(3, $track->failed_rows);
        $this->assertNotNull($track->error_file_path);
        $this->assertNotNull($track->error_log);

        // Assert error file exists and has failed rows with details
        Storage::disk('local')->assertExists($track->error_file_path);
        
        $errorCsvContent = Storage::disk('local')->get($track->error_file_path);
        $this->assertStringContainsString('permission to assign the \'Super Admin\' role', $errorCsvContent);
        $this->assertStringContainsString('The email field must be a valid email address', $errorCsvContent);
        $this->assertStringContainsString('Company \'NON_EXISTENT\' not found', $errorCsvContent);
    }
}
