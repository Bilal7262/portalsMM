<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminVoice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminVoiceController extends Controller
{
    /**
     * Display a listing of admin voices
     */
    public function index(Request $request)
    {
        $query = AdminVoice::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $voices = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json($voices);
    }

    /**
     * Store a newly created voice
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:admin_voices,name',
            'transcript' => 'nullable|string',
            'scene_prompt' => 'nullable|string',
            'ref_audio' => 'nullable|string',
            'ref_audio_in_system_message' => 'boolean',
            'chunk_method' => 'string',
            'chunk_max_word_num' => 'integer|min:1',
            'chunk_max_num_turns' => 'integer|min:1',
            'generation_chunk_buffer_size' => 'integer|min:1',
            'temperature' => 'numeric|min:0|max:9.99',
            'top_k' => 'integer|min:1',
            'top_p' => 'numeric|min:0|max:1',
            'ras_win_len' => 'integer|min:1',
            'ras_win_max_num_repeat' => 'integer|min:1',
            'seed' => 'nullable|integer',
            'status' => 'in:active,inactive',
        ]);

        $voice = AdminVoice::create($validated);

        return response()->json($voice, 201);
    }

    /**
     * Display the specified voice
     */
    public function show($id)
    {
        $voice = AdminVoice::with('companyAgents')->findOrFail($id);
        return response()->json($voice);
    }

    /**
     * Update the specified voice
     */
    public function update(Request $request, $id)
    {
        $voice = AdminVoice::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255|unique:admin_voices,name,' . $id,
            'transcript' => 'nullable|string',
            'scene_prompt' => 'nullable|string',
            'ref_audio' => 'nullable|string',
            'ref_audio_in_system_message' => 'boolean',
            'chunk_method' => 'string',
            'chunk_max_word_num' => 'integer|min:1',
            'chunk_max_num_turns' => 'integer|min:1',
            'generation_chunk_buffer_size' => 'integer|min:1',
            'temperature' => 'numeric|min:0|max:9.99',
            'top_k' => 'integer|min:1',
            'top_p' => 'numeric|min:0|max:1',
            'ras_win_len' => 'integer|min:1',
            'ras_win_max_num_repeat' => 'integer|min:1',
            'seed' => 'nullable|integer',
            'status' => 'in:active,inactive',
        ]);

        $voice->update($validated);

        return response()->json($voice);
    }

    /**
     * Remove the specified voice (soft delete)
     */
    public function destroy($id)
    {
        $voice = AdminVoice::findOrFail($id);
        $voice->delete();

        return response()->json(['message' => 'Voice deleted successfully']);
    }

    /**
     * Get all caches for a specific voice
     */
    public function getCaches(Request $request, $id)
    {
        $voice = AdminVoice::findOrFail($id);

        $query = $voice->voiceCaches()->with('companyAgent.company');

        // Search filter (company agent, company name, cache_key, message)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cache_key', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('companyAgent', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhereHas('company', function ($q3) use ($search) {
                                $q3->where('business_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Company Agent filter
        if ($request->filled('company_agent_id')) {
            $query->where('company_agent_id', $request->company_agent_id);
        }

        // Hit status filter
        if ($request->filled('hit')) {
            $query->where('hit', $request->hit == '1');
        }

        $caches = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json($caches);
    }
}
