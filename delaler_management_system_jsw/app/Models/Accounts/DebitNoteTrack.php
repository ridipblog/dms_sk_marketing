<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNoteTrack extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_track_id',
        'nos',
        'amount',
        'base_amount',
        'gst_amount',
        'reason',
        'parent_payment_track_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
    ];

    public function paymentTrack()
    {
        return $this->belongsTo(PaymentTrack::class, 'payment_track_id');
    }
}
