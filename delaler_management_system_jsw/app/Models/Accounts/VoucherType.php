<?php

namespace App\Models\Accounts;

use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model
{
    protected $table = 'voucher_types';
    
    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
