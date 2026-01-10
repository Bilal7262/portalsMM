<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'company_user_id',
        'activity_type',
        'activity_details',
        'activity_date',
    ];

    protected $casts = [
        'activity_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(CompanyUser::class, 'company_user_id');
    }
}
