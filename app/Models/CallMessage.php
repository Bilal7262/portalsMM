<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'type',
        'audio',
        'text',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Get the call this message belongs to
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }
}
