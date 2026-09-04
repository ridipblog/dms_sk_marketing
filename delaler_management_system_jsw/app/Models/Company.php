<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Category;
use App\Models\Dealers\DealerCompany;
use App\Models\Accounts\CashDiscountSlab;

class Company extends Model
{
    protected $table = "companies";
    protected $fillable = [
        'company_code',
        'company_name',
        'email',
        'phone',
        'gst_no',
        'pan_no',
        'address',
        'status',
    ];
    public function userRoleCompanies()
    {
        return $this->hasMany(UserRoleCompany::class, 'company_id');
    }
    public function categories()
    {
        return $this->hasMany(Category::class, 'company_id');
    }
    public function cashDiscountSlabs()
    {
        return $this->hasMany(CashDiscountSlab::class, 'company_id');
    }
    public function dealerCompany()
    {
        return $this->hasMany(DealerCompany::class, 'company_id');
    }
    public function bankDetail()
    {
        return $this->hasOne(CompanyBankDetail::class, 'company_id');
    }
}
