<?php

namespace App\Models\Inventory;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = [
        'category_name',
        'category_code',
        'company_id',
        'description',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
