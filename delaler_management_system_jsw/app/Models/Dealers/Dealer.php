<?php

namespace App\Models\Dealers;

use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    protected $table = "dealers";
    protected $fillable = [
        'dealer_name',
        'pan_number',
        'email',
        'phone',
        'address',
        'dealer_code',
        'gst_number',
        'status',
    ];

    public function dealerCompany()
    {
        return $this->hasMany(DealerCompany::class, 'dealer_id');
    }
}
