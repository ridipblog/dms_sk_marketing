<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBankDetail extends Model
{
    protected $table = 'company_bank_details';

    protected $fillable = [
        'company_id',
        'account_holder_name',
        'bank_name',
        'account_no',
        'ifsc_code',
        'swift_code',
        'branch_name',
        'upi_id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
