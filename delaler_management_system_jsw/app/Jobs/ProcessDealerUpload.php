<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Dealers\Dealer;
use App\Models\Dealers\DealerCompany;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProcessDealerUpload implements ShouldQueue
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
            }, storage_path('app/private/' . $this->filePath));

            if (empty($data) || empty($data[0])) {
                throw new \Exception("The uploaded file is empty or invalid format.");
            }

            $rows = $data[0];
            $header = array_shift($rows);

            $track->update(['total_rows' => count($rows)]);

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                if (count($row) < 8) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": Incomplete data.";
                    continue;
                }

                $dealerCode  = trim($row[0] ?? '');
                $dealerName  = trim($row[1] ?? '');
                $email       = trim($row[2] ?? '');
                $phone       = trim($row[3] ?? '');
                $panNumber   = trim($row[4] ?? '');
                $gstNumber   = trim($row[5] ?? '');
                $address     = trim($row[6] ?? '');
                $status      = strtolower(trim($row[7] ?? '')) === 'inactive' ? 'inactive' : 'active';

                $existingDealer = Dealer::where('dealer_code', $dealerCode)->first();
                $dealerId = $existingDealer ? $existingDealer->id : null;

                $data = [
                    'dealer_name' => $dealerName,
                    'dealer_code' => $dealerCode,
                    'email' => $email,
                    'phone' => $phone,
                    'pan_number' => $panNumber,
                    'gst_number' => $gstNumber,
                    'address' => $address,
                ];

                $validator = Validator::make($data, [
                    'dealer_name' => 'required|string|max:255',
                    'dealer_code' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'phone' => 'required|string|max:10' . ($dealerId ? '|unique:dealers,phone,' . $dealerId : '|unique:dealers,phone'),
                    'pan_number' => 'required|string|max:50',
                    'gst_number' => 'required|string|max:50',
                    'address' => 'nullable|string',
                ]);

                if ($validator->fails()) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": " . implode(", ", $validator->errors()->all());
                    continue;
                }

                // Custom validation: prevent duplicate company-wise for gst, pan
                $companyId = $track->company_id;
                
                if ($gstNumber || $panNumber) {
                    $duplicateQuery = Dealer::whereHas('dealerCompany', function($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })->where(function($q) use ($gstNumber, $panNumber) {
                        if ($gstNumber) $q->orWhere('gst_number', $gstNumber);
                        if ($panNumber) $q->orWhere('pan_number', $panNumber);
                    });

                    if ($dealerId) {
                        $duplicateQuery->where('id', '!=', $dealerId);
                    }

                    if ($duplicateQuery->exists()) {
                        $failed++;
                        $errors[] = "Row " . ($index + 2) . ": Dealer duplicate found (GST or PAN already exists for this company).";
                        continue;
                    }
                }

                try {
                    DB::beginTransaction();

                    $dealer = Dealer::updateOrCreate(
                        ['dealer_code' => $dealerCode],
                        [
                            'dealer_name' => $dealerName,
                            'email' => $email ?: null,
                            'phone' => $phone ?: null,
                            'pan_number' => $panNumber ?: null,
                            'gst_number' => $gstNumber ?: null,
                            'address' => $address ?: null,
                            'status' => $status
                        ]
                    );

                    $dealerCompany = DealerCompany::where('dealer_id', $dealer->id)
                                                  ->where('company_id', $track->company_id)
                                                  ->first();
                    
                    if ($dealerCompany) {
                        $dealerCompany->update([
                            'status' => $status,
                            'updated_by' => $track->user_id
                        ]);
                    } else {
                        DealerCompany::create([
                            'dealer_id' => $dealer->id,
                            'company_id' => $track->company_id,
                            'status' => $status,
                            'created_by' => $track->user_id,
                            'updated_by' => $track->user_id,
                            'total_debit_note_amount' => 0,
                            'total_credit_note_amount' => 0
                        ]);
                    }

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $track->update([
                'status' => 'completed',
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'error_log' => empty($errors) ? null : implode("\n", $errors)
            ]);

            Storage::disk('local')->delete($this->filePath);

        } catch (\Throwable $e) {
            Log::error('Dealer Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }
}
