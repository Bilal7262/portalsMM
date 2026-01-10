<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Call extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_did_invoice_id',
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

    public function invoice()
    {
        return $this->belongsTo(CompanyDidInvoice::class, 'company_did_invoice_id');
    }
}
