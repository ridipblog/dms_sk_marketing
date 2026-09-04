<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadTrack;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductPricing;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessInventoryUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trackId;
    protected $filePath;

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
            // Excel facade can automatically parse based on file extension
            // We pass a generic class to just extract the array
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, storage_path('app/private/' . $this->filePath));

            if (empty($data) || empty($data[0])) {
                throw new \Exception("The uploaded file is empty or invalid format.");
            }

            $rows = $data[0];
            // Remove header row
            $header = array_shift($rows);

            $track->update(['total_rows' => count($rows)]);

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                // Ensure row has required columns
                if (count($row) < 13) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": Incomplete data.";
                    continue;
                }

                $catName     = trim($row[0] ?? '');
                $catCode     = trim($row[1] ?? '');
                $catDesc     = trim($row[2] ?? '');
                $prodName    = trim($row[3] ?? '');
                $skuCode     = trim($row[4] ?? '');
                $hsnCode     = trim($row[5] ?? '');
                $size        = trim($row[6] ?? '');
                $unit        = trim($row[7] ?? '');
                $basePrice   = trim($row[8] ?? '') !== '' ? trim($row[8]) : null;
                $pricePerMt  = trim($row[9] ?? '') !== '' ? trim($row[9]) : null;
                $priceType   = trim($row[10] ?? '');
                $gstPct      = trim($row[11] ?? '') !== '' ? trim($row[11]) : null;
                $discount    = trim($row[12] ?? '') !== '' ? trim($row[12]) : null;
                $effFrom     = trim($row[13] ?? '');
                $effTo       = trim($row[14] ?? '');

                // Format effective dates, fallback to today if missing to satisfy database constraints
                if (empty($effFrom)) {
                    $effFrom = \Carbon\Carbon::now()->format('Y-m-d');
                } else {
                    try {
                        $effFrom = \Carbon\Carbon::parse($effFrom)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $effFrom = \Carbon\Carbon::now()->format('Y-m-d');
                    }
                }

                if (!empty($effTo)) {
                    try {
                        $effTo = \Carbon\Carbon::parse($effTo)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $effTo = null;
                    }
                } else {
                    $effTo = null;
                }

                if (empty($catCode) || empty($skuCode) || $pricePerMt === null) {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": Missing required fields (Category Code, SKU Code, or Price per MT).";
                    continue;
                }

                try {
                    DB::beginTransaction();

                    // Category
                    $category = Category::firstOrCreate(
                        ['category_code' => $catCode, 'company_id' => $track->company_id],
                        ['category_name' => $catName, 'description' => $catDesc, 'status' => 'active']
                    );

                    // Product
                    $product = Product::firstOrCreate(
                        ['sku_code' => $skuCode],
                        [
                            'category_id' => $category->id,
                            'product_name' => $prodName,
                            'hsn_code' => $hsnCode,
                            'size' => $size,
                            'unit' => $unit,
                            'base_price' => $basePrice,
                            'status' => 'active'
                        ]
                    );

                    // Check if another active price exists for this product
                    $hasOtherActive = ProductPricing::where('product_id', $product->id)
                        ->where('status', 'active')
                        // ->where('price_type', '!=', $priceType)
                        ->exists();

                    $pricingStatus = $hasOtherActive ? 'inactive' : 'active';

                    // Pricing
                    /*
                    ProductPricing::updateOrCreate(
                        ['product_id' => $product->id, 'price_type' => $priceType],
                        [
                            'price_per_mt' => $pricePerMt,
                            'gst_percentage' => $gstPct,
                            'discount_amount' => $discount,
                            'effective_from' => $effFrom,
                            'effective_to' => $effTo,
                            'status' => $pricingStatus
                        ]
                    );
                    */

                    ProductPricing::create([
                        'product_id' => $product->id,
                        'price_type' => $priceType,
                        'price_per_mt' => $pricePerMt,
                        'gst_percentage' => $gstPct,
                        'discount_amount' => $discount,
                        'status' => $pricingStatus
                    ]);

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
        } catch (\Throwable $e) {
            Log::error('Excel Upload Job Error: ' . $e->getMessage());
            $track->update([
                'status' => 'failed',
                'error_log' => $e->getMessage()
            ]);
        }
    }
}
