<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyDidInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_did_id',
        'effective_from',
        'effective_to',
        'total_minutes_consumption',
        'billed_amount',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'billed_amount' => 'decimal:2',
    ];

    public function companyDid()
    {
        return $this->belongsTo(CompanyDid::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
}
