<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = "products";
    protected $fillable = [
        'category_id',
        'product_name',
        'sku_code',
        'hsn_code',
        'size',
        'unit',
        'base_price',
        'stock_quantity',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'stock_quantity' => 'decimal:3',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function pricings()
    {
        return $this->hasMany(ProductPricing::class, 'product_id');
    }
}
