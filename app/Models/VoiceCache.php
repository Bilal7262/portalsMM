<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoiceCache extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'voice_cache';

    protected $fillable = [
        'voice_id',
        'company_agent_id',
        'cache_key',
        'hit',
        'hit_count',
        'message',
    ];

    protected $casts = [
        'hit' => 'boolean',
        'hit_count' => 'integer',
        'cache_key' => 'integer',
    ];

    /**
     * Get the admin voice that owns this cache
     */
    public function voice()
    {
        return $this->belongsTo(AdminVoice::class, 'voice_id');
    }

    /**
     * Get the company agent that owns this cache
     */
    public function companyAgent()
    {
        return $this->belongsTo(CompanyAgent::class);
    }

    /**
     * Get or create cache for a voice and company agent
     */
    public static function getOrCreate($voiceId, $companyAgentId)
    {
        return self::firstOrCreate(
            [
                'voice_id' => $voiceId,
                'company_agent_id' => $companyAgentId,
            ],
            [
                'cache_key' => 0,
                'hit' => false,
                'hit_count' => 0,
            ]
        );
    }

    /**
     * Increment hit count and mark as hit
     */
    public function incrementHit()
    {
        $this->increment('hit_count');
        if (!$this->hit) {
            $this->update(['hit' => true]);
        }
        return $this;
    }

    /**
     * Reset hit tracking
     */
    public function resetHits()
    {
        return $this->update([
            'hit' => false,
            'hit_count' => 0,
        ]);
    }

    /**
     * Invalidate cache (soft delete)
     */
    public function invalidate()
    {
        return $this->delete();
    }

    /**
     * Clear all caches for a specific voice
     */
    public static function clearByVoice($voiceId)
    {
        return self::where('voice_id', $voiceId)->delete();
    }

    /**
     * Clear all caches for a specific company agent
     */
    public static function clearByAgent($companyAgentId)
    {
        return self::where('company_agent_id', $companyAgentId)->delete();
    }
}
