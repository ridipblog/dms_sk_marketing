<?php

namespace App\Models\Reports;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyStockReport extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'date',
        'opening_stock',
        'sale_quantity',
        'purchase_quantity',
        'closing_stock',
    ];

    protected $casts = [
        'opening_stock' => 'decimal:3',
        'sale_quantity' => 'decimal:3',
        'purchase_quantity' => 'decimal:3',
        'closing_stock' => 'decimal:3',
        'date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Record or update sales quantity for a specific company, product and date.
     */
    public static function recordSale(int $companyId, int $productId, string $date, float $saleQuantity): self
    {
        return static::recordTransaction($companyId, $productId, $date, $saleQuantity, 0);
    }

    /**
     * Record or update purchase quantity for a specific company, product and date.
     */
    public static function recordPurchase(int $companyId, int $productId, string $date, float $purchaseQuantity): self
    {
        return static::recordTransaction($companyId, $productId, $date, 0, $purchaseQuantity);
    }

    /**
     * Record or update daily product stock transaction and recalculate stock balances.
     */
    public static function recordTransaction(int $companyId, int $productId, string $date, float $addSale, float $addPurchase): self
    {
        $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');

        return DB::transaction(function () use ($companyId, $productId, $formattedDate, $addSale, $addPurchase) {
            $record = static::where('company_id', $companyId)
                ->where('product_id', $productId)
                ->where('date', $formattedDate)
                ->lockForUpdate()
                ->first();

            if ($record) {
                $record->sale_quantity += $addSale;
                $record->purchase_quantity += $addPurchase;
                $record->closing_stock = $record->opening_stock + $record->purchase_quantity - $record->sale_quantity;
                $record->save();
            } else {
                // Find previous date closing stock for this product to set as opening stock
                $prevRecord = static::where('company_id', $companyId)
                    ->where('product_id', $productId)
                    ->where('date', '<', $formattedDate)
                    ->orderBy('date', 'desc')
                    ->first();

                $openingStock = $prevRecord ? (float)$prevRecord->closing_stock : 0.000;
                $closingStock = $openingStock + $addPurchase - $addSale;

                $record = static::create([
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'date' => $formattedDate,
                    'opening_stock' => $openingStock,
                    'sale_quantity' => $addSale,
                    'purchase_quantity' => $addPurchase,
                    'closing_stock' => $closingStock,
                ]);
            }

            // Recalculate any subsequent records after this date for this product
            static::cascadeStockUpdates($companyId, $productId, $formattedDate);

            return $record;
        });
    }

    /**
     * Cascade opening/closing stock updates for dates following a target date for a specific product.
     */
    public static function cascadeStockUpdates(int $companyId, int $productId, string $startDate): void
    {
        $subsequentRecords = static::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('date', '>', $startDate)
            ->orderBy('date', 'asc')
            ->get();

        if ($subsequentRecords->isEmpty()) {
            return;
        }

        $runningClosingStock = (float) static::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('date', $startDate)
            ->value('closing_stock');

        foreach ($subsequentRecords as $rec) {
            $rec->opening_stock = $runningClosingStock;
            $rec->closing_stock = $rec->opening_stock + $rec->purchase_quantity - $rec->sale_quantity;
            $rec->save();

            $runningClosingStock = (float) $rec->closing_stock;
        }
    }
}
