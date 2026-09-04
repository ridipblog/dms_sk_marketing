<?php

namespace App\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UploadTrack extends Model
{
    protected $table = 'upload_tracks';

    protected $fillable = [
        'user_id',
        'company_id',
        'role_user_company_id',
        'file_name',
        'status',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'error_log',
        'error_file_path',
        'upload_type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function userRoleCompany()
    {
        return $this->belongsTo(UserRoleCompany::class, 'role_user_company_id');
    }
}
