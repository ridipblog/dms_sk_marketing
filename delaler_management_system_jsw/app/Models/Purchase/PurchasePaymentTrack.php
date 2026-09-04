<?php

namespace App\Models\Purchase;

use App\Models\Accounts\VoucherType;
use App\Models\UserRoleCompany;
use Illuminate\Database\Eloquent\Model;

class PurchasePaymentTrack extends Model
{
    protected $table = 'purchase_payment_tracks';

    protected $fillable = [
        'purchase_invoice_id',
        'transaction_id',
        'amount',
        'balance_amount',
        'payment_mode',
        'voucher_type_id',
        'transaction_date',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(UserRoleCompany::class, 'created_by');
    }
}
