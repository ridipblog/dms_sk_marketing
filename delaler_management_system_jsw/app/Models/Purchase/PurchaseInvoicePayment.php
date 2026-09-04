<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoicePayment extends Model
{
    protected $table = 'purchase_invoice_payments';

    protected $fillable = [
        'purchase_invoice_id',
        'outstanding_amount',
        'paid_amount',
        'clear_status'
    ];

    protected $casts = [
        'outstanding_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
