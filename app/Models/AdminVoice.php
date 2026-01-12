<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminVoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'transcript',
        'scene_prompt',
        'ref_audio',
        'ref_audio_in_system_message',
        'chunk_method',
        'chunk_max_word_num',
        'chunk_max_num_turns',
        'generation_chunk_buffer_size',
        'temperature',
        'top_k',
        'top_p',
        'ras_win_len',
        'ras_win_max_num_repeat',
        'seed',
        'status',
    ];

    protected $casts = [
        'ref_audio_in_system_message' => 'boolean',
        'temperature' => 'decimal:2',
        'top_p' => 'decimal:2',
        'chunk_max_word_num' => 'integer',
        'chunk_max_num_turns' => 'integer',
        'generation_chunk_buffer_size' => 'integer',
        'top_k' => 'integer',
        'ras_win_len' => 'integer',
        'ras_win_max_num_repeat' => 'integer',
        'seed' => 'integer',
    ];

    /**
     * Get the company agents using this voice
     */
    public function companyAgents()
    {
        return $this->hasMany(CompanyAgent::class);
    }
}
