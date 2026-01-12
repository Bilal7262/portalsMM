<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\CallMessage;
use Illuminate\Http\Request;

class CallMessageController extends Controller
{
    /**
     * Get all messages for a specific call
     */
    public function index($callId)
    {
        $messages = CallMessage::where('call_id', $callId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Store a new call message
     */
    public function store(Request $request, $callId)
    {
        $validated = $request->validate([
            'type' => 'required|in:user,bot',
            'audio' => 'nullable|string',
            'text' => 'required|string',
        ]);

        $message = CallMessage::create([
            'call_id' => $callId,
            'type' => $validated['type'],
            'audio' => $validated['audio'] ?? null,
            'text' => $validated['text'],
        ]);

        return response()->json($message, 201);
    }
}
