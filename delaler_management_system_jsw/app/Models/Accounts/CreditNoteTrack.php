<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNoteTrack extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_track_id',
        'cash_discount_slab_id',
        'nos',
        'amount',
        'parent_payment_track_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function paymentTrack()
    {
        return $this->belongsTo(PaymentTrack::class, 'payment_track_id');
    }

    public function cashDiscountSlab()
    {
        return $this->belongsTo(CashDiscountSlab::class, 'cash_discount_slab_id');
    }
}
