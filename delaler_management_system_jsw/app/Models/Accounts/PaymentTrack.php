<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTrack extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'amount',
        'balance_amount',
        'scheme_amount',
        'payment_for_mt',
        'payment_mode',
        'voucher_type_id',
        'transaction_date',
        'remarks',
        'modified_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'scheme_amount' => 'decimal:2',
        'payment_for_mt' => 'decimal:3',
        'transaction_date' => 'datetime',
    ];



    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function voucherTypeModel()
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }

    public function debitNoteTracks()
    {
        return $this->hasMany(DebitNoteTrack::class, 'payment_track_id');
    }
    public function creditNoteTracks()
    {
        return $this->hasMany(CreditNoteTrack::class, 'payment_track_id');
    }
}
