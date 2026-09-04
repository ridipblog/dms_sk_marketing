<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class ProductPricing extends Model
{
    protected $table = "product_pricings";
    protected $fillable = [
        'product_id',
        'price_per_mt',
        'gst_percentage',
        'discount_amount',
        'price_type',
        'status',
    ];

    protected $casts = [
        'price_per_mt' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
