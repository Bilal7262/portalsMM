<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Traits\LaratrustUserTrait;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasFactory, SoftDeletes, LaratrustUserTrait, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function activityLogs()
    {
        return $this->hasMany(AdminActivityLog::class);
    }
}
