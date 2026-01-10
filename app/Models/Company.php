<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'phone',
        'cnic_passport_url',
        'business_name',
        'business_address',
        'verify_email',
        'verify_phone',
        'status',
    ];

    protected $casts = [
        'verify_email' => 'boolean',
        'verify_phone' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function dids()
    {
        return $this->hasMany(CompanyDid::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(CompanyActivityLog::class);
    }
}
