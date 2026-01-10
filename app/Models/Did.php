<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Did extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'did_number',
        'status',
    ];

    public function companyDids()
    {
        return $this->hasMany(CompanyDid::class);
    }
}
