<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\UserRoleCompany;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ProcessUserUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trackId;
    protected $filePath;
    public $timeout = 3600; // 1 hour

    public function __construct($trackId, $filePath)
    {
        $this->trackId = $trackId;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $track = UploadTrack::find($this->trackId);
        if (!$track) return;

        $track->update(['status' => 'processing']);

        try {
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, Storage::disk('local')->path($this->filePath));

            if (empty($data) || empty($data[0])) {
                throw new \Exception("The uploaded file is empty or invalid format.");
            }

            $rows = $data[0];
            $header = array_shift($rows);

            $track->update(['total_rows' => count($rows)]);

            $imported = 0;
            $failed = 0;
            $errors = [];
            $failedRows = [];

            // Resolve the active priority of the uploading user
            $activeMapping = UserRoleCompany::with('role')->find($track->role_user_company_id);
            $activePriority = $activeMapping && $activeMapping->role ? $activeMapping->role->priority : 0;

            foreach ($rows as $index => $row) {
                // If row is entirely empty, skip
                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($row) < 7) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": Incomplete data columns.";
                    $failedRows[] = array_merge($row, ["Incomplete data columns."]);
                    continue;
                }

                $name             = trim($row[0] ?? '');
                $phone            = trim($row[1] ?? '');
                $email            = trim($row[2] ?? '');
                $designation      = trim($row[3] ?? '');
                $rawStatus        = strtolower(trim($row[4] ?? ''));
                $companyCode      = trim($row[5] ?? '');
                $roleName         = trim($row[6] ?? '');
                $reportingManager = trim($row[7] ?? '');

                // Status mapping
                $status = 'active';
                if ($rawStatus === 'inactive') {
                    $status = 'inactive';
                } elseif ($rawStatus === 'blocked') {
                    $status = 'blocked';
                }

                // Row-level validation
                $rowData = [
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'company_code' => $companyCode,
                    'role_name' => $roleName,
                ];

                $validator = Validator::make($rowData, [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'email' => 'required|email|max:255',
                    'company_code' => 'required|string',
                    'role_name' => 'required|string',
                ]);

                if ($validator->fails()) {
                    $failed++;
                    $errMsg = implode(", ", $validator->errors()->all());
                    $errors[] = "Row " . ($index + 2) . ": " . $errMsg;
                    $failedRows[] = array_merge($row, [$errMsg]);
                    continue;
                }

                $inTransaction = false;
                try {
                    // 1. Resolve Company
                    $company = Company::where('company_code', $companyCode)
                        ->orWhere('company_name', $companyCode)
                        ->first();
                    if (!$company) {
                        throw new \Exception("Company '{$companyCode}' not found.");
                    }

                    // 2. Resolve Role
                    $role = Role::where('role_name', $roleName)->first();
                    if (!$role) {
                        throw new \Exception("Role '{$roleName}' not found.");
                    }

                    // 3. Enforce Role Priority Assignment Logic
                    if ($role->priority <= $activePriority) {
                        throw new \Exception("You do not have permission to assign the '{$roleName}' role (your active priority: {$activePriority}, role priority: {$role->priority}).");
                    }

                    // 4. Hierarchy Validation and Reporting Manager Resolution
                    $parent_id = null;
                    $needsParent = array_key_exists($roleName, config('hierarchy.relations', []));
                    if ($needsParent) {
                        if (empty($reportingManager)) {
                            throw new \Exception("Reporting manager (Reporting Phone/Email) is required for hierarchy role '{$roleName}'.");
                        }

                        $parentUser = User::where('phone', $reportingManager)
                            ->orWhere('email', $reportingManager)
                            ->first();

                        if (!$parentUser) {
                            throw new \Exception("Reporting manager user '{$reportingManager}' not found.");
                        }

                        $parentRoleName = config("hierarchy.relations.{$roleName}");
                        $parentRole = Role::where('role_name', $parentRoleName)->first();
                        if (!$parentRole) {
                            throw new \Exception("Manager role '{$parentRoleName}' does not exist in the database configuration.");
                        }

                        // Verify manager is active and holds the required role in the selected company
                        $hasParentMapping = UserRoleCompany::where('user_id', $parentUser->id)
                            ->where('company_id', $company->id)
                            ->where('role_id', $parentRole->id)
                            ->exists();

                        if (!$hasParentMapping) {
                            throw new \Exception("Reporting user '{$parentUser->name}' does not have the manager role '{$parentRoleName}' assigned in company '{$companyCode}'.");
                        }

                        $parent_id = $parentUser->id;
                    }

                    // 5. Check for User Conflicts (existing user by phone vs email)
                    $existingUserByPhone = User::where('phone', $phone)->first();
                    $existingUserByEmail = User::where('email', $email)->first();

                    if ($existingUserByPhone && $existingUserByEmail && $existingUserByPhone->id !== $existingUserByEmail->id) {
                        throw new \Exception("Conflict: Phone matches user '{$existingUserByPhone->name}' but Email matches a different user '{$existingUserByEmail->name}'.");
                    }

                    $user = $existingUserByPhone ?: $existingUserByEmail;

                    DB::beginTransaction();
                    $inTransaction = true;

                    if ($user) {
                        // Update existing user details
                        $user->update([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'designation' => $designation ?: $user->designation,
                            'status' => $status
                        ]);
                    } else {
                        // Create new user
                        $user = User::create([
                            'name' => $name,
                            'phone' => $phone,
                            'email' => $email,
                            'password' => Hash::make('12345678'), // Default password
                            'designation' => $designation ?: null,
                            'status' => $status
                        ]);
                    }

                    // 6. Establish or Update UserRoleCompany mapping
                    $existingMapping = UserRoleCompany::where('user_id', $user->id)
                        ->where('company_id', $company->id)
                        ->where('role_id', $role->id)
                        ->first();

                    if ($existingMapping) {
                        $existingMapping->update([
                            'parent_id' => $parent_id
                        ]);
                    } else {
                        UserRoleCompany::create([
                            'user_id' => $user->id,
                            'company_id' => $company->id,
                            'role_id' => $role->id,
                            'parent_id' => $parent_id
                        ]);
                    }

                    DB::commit();
                    $inTransaction = false;
                    $imported++;
                } catch (\Exception $e) {
                    if ($inTransaction) {
                        DB::rollBack();
                    }
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $failedRows[] = array_merge($row, [$e->getMessage()]);
                }
            }

            $errorFilePath = null;
            if (!empty($failedRows)) {
                $errorFilePath = 'user_uploads/errors/error_' . $track->id . '_' . time() . '.csv';

                // Ensure errors directory exists with proper permissions
                $errorsDir = Storage::disk('local')->path('user_uploads/errors');
                if (!file_exists($errorsDir)) {
                    @mkdir($errorsDir, 0775, true);
                    @chmod($errorsDir, 0775);
                }

                $tempPath = tempnam(sys_get_temp_dir(), 'user_upload_err_');
                $handle = fopen($tempPath, 'w');

                fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
                fputcsv($handle, ['Name', 'Phone', 'Email', 'Designation', 'Status', 'Company Code', 'Role Name', 'Reporting Phone/Email', 'Error Reason']);

                foreach ($failedRows as $failedRow) {
                    fputcsv($handle, $failedRow);
                }
                fclose($handle);

                Storage::disk('local')->put($errorFilePath, file_get_contents($tempPath));
                @chmod(Storage::disk('local')->path($errorFilePath), 0664);
                unlink($tempPath);
            }

            $track->update([
                'status' => 'completed',
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'error_file_path' => $errorFilePath,
                'error_log' => empty($errors) ? null : implode("\n", $errors)
            ]);

            Storage::disk('local')->delete($this->filePath);
        } catch (\Throwable $e) {
            Log::error('User Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}
