<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_agent_invoice_item_id',
        'session_id',
        'user_phone',
        'call_audio_url',
        'call_transcription',
        'duration',
        'disposition',
        'scheduled_callback',
        'ai_feedback',
        'ai_rating',
        'company_feedback',
        'company_rating',
    ];

    protected $casts = [
        'scheduled_callback' => 'datetime',
    ];

    public function invoiceItem()
    {
        return $this->belongsTo(CompanyAgentInvoiceItem::class, 'company_agent_invoice_item_id');
    }

    public function messages()
    {
        return $this->hasMany(CallMessage::class);
    }
}
