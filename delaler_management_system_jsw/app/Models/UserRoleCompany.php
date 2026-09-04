<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleCompany extends Model
{
    protected $table = "user_role_companies";
    protected $fillable = [
        'user_id',
        'role_id',
        'company_id',
        'parent_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
