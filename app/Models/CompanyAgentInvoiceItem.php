<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyAgentInvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_agent_invoice_id',
        'company_agent_id',
        'total_minutes',
        'rate_per_min',
        'subtotal',
    ];

    protected $casts = [
        'total_minutes' => 'integer',
        'rate_per_min' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the invoice this item belongs to
     */
    public function invoice()
    {
        return $this->belongsTo(CompanyAgentInvoice::class, 'company_agent_invoice_id');
    }

    /**
     * Get the agent this item is for
     */
    public function agent()
    {
        return $this->belongsTo(CompanyAgent::class, 'company_agent_id');
    }

    /**
     * Get the calls associated with this invoice item
     */
    public function calls()
    {
        return $this->hasMany(Call::class, 'company_agent_invoice_item_id');
    }
}
