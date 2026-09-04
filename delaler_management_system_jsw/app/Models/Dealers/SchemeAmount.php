<?php

namespace App\Models\Dealers;

use App\Models\UserRoleCompany;
use Illuminate\Database\Eloquent\Model;

class SchemeAmount extends Model
{
    protected $table = "scheme_amounts";

    protected $fillable = [
        'dealer_company_id',
        'quantity',
        'month',
        'year',
        'rate_per_mt',
        'amount',
        'is_used',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'month' => 'integer',
        'year' => 'integer',
        'rate_per_mt' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_used' => 'boolean',
    ];

    public function dealerCompany()
    {
        return $this->belongsTo(DealerCompany::class, 'dealer_company_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(UserRoleCompany::class, 'created_by');
    }
}
