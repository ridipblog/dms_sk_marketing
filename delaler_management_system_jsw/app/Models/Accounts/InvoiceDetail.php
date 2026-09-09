<?php

namespace App\Models\Accounts;

use App\Models\Inventory\ProductPricing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDetail extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_id',
        'product_pricing_id',
        'custom_price',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'gst_amount',
        'total_amount',
        'chargeable_amount',
        'quantity',
        'round_of',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function productPricing()
    {
        return $this->belongsTo(ProductPricing::class, 'product_pricing_id');
    }
}
