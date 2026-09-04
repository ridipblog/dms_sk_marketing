<?php

namespace App\Models\Dealers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DealerCompany extends Model
{
    protected $table = "dealer_companies";

    protected $fillable = [
        'dealer_id',
        'company_id',
        'aso_id',
        'opening_balance',
        'total_debit_note_amount',
        'total_credit_note_amount',
        'total_scheme_amount',
        'created_by',
        'updated_by',
        'status',
    ];


    protected $casts = [
        'opening_balance' => 'decimal:2',
        'total_debit_note_amount' => 'decimal:2',
        'total_credit_note_amount' => 'decimal:2',
        'total_scheme_amount' => 'decimal:2',
    ];

    public function schemeAmounts()
    {
        return $this->hasMany(SchemeAmount::class, 'dealer_company_id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function aso()
    {
        return $this->belongsTo(User::class, 'aso_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
