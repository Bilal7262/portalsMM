<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyDid extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'did_id',
        'price_per_min',
        'start_date',
        'end_date',
        'status',
        'last_use',
    ];

    protected $casts = [
        'price_per_min' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_use' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function did()
    {
        return $this->belongsTo(Did::class);
    }

    public function invoices()
    {
        return $this->hasMany(CompanyDidInvoice::class);
    }
}
