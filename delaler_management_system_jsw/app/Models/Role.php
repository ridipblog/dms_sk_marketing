<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = "roles";
    protected $fillable = [
        'role_name',
        'description',
        'status',
        'priority'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function userRoleCompanies()
    {
        return $this->hasMany(UserRoleCompany::class, 'role_id');
    }
}
