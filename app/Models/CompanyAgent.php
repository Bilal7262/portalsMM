<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyAgent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'did_id',
        'admin_voice_id',
        'name',
        'script',
        'quantity',
        'price_per_min',
        'status',
        'start_date',
        'end_date',
        'last_use',
    ];

    protected $casts = [
        'price_per_min' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_use' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * Get the company that owns the agent
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the DID assigned to this agent
     */
    public function did()
    {
        return $this->belongsTo(Did::class);
    }

    /**
     * Get the admin voice configuration for this agent
     */
    public function adminVoice()
    {
        return $this->belongsTo(AdminVoice::class);
    }

    /**
     * Get the invoice items for this agent
     */
    public function invoiceItems()
    {
        return $this->hasMany(CompanyAgentInvoiceItem::class);
    }

    /**
     * Get the invoices this agent belongs to (through invoice items)
     */
    public function invoices()
    {
        return $this->belongsToMany(CompanyAgentInvoice::class, 'company_agent_invoice_items')
            ->withPivot('total_minutes', 'rate_per_min', 'subtotal')
            ->withTimestamps();
    }

    /**
     * Get the voice caches for this agent
     */
    public function voiceCaches()
    {
        return $this->hasMany(VoiceCache::class);
    }
}
