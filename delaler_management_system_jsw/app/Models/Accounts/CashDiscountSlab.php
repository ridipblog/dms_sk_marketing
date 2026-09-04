<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;

class CashDiscountSlab extends Model
{
    protected $fillable = [
        'company_id',
        'slab_name',
        'minimum_days',
        'maximum_days',
        'discount_percent',
        'status',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creditNoteTracks()
    {
        return $this->hasMany(CreditNoteTrack::class, 'cash_discount_slab_id');
    }
}
