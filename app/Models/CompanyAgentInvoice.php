<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyAgentInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'invoice_number',
        'effective_from',
        'effective_to',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns the invoice
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the invoice items for this invoice
     */
    public function items()
    {
        return $this->hasMany(CompanyAgentInvoiceItem::class);
    }

    /**
     * Get the agents included in this invoice (through invoice items)
     */
    public function agents()
    {
        return $this->belongsToMany(CompanyAgent::class, 'company_agent_invoice_items')
            ->withPivot('total_minutes', 'rate_per_min', 'subtotal')
            ->withTimestamps();
    }
}
