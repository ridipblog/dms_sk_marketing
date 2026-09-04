<?php

namespace App\Models\Purchase;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceDetail extends Model
{
    protected $table = 'purchase_invoice_details';

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'quantity',
        'rate',
        'total_amount',
        'cgst_amount',
        'sgst_amount',
        'gst_amount',
        'chargeable_amount'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'chargeable_amount' => 'decimal:2',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
