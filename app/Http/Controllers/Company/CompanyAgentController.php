<?php

namespace App\Http\Controllers\Company;

use App\Models\CompanyAgent;
use App\Models\AdminVoice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyAgentController extends Controller
{
    /**
     * Display a listing of company's agents
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        
        $query = CompanyAgent::where('company_id', $companyId)
            ->with(['did', 'adminVoice']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or DID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('did', function ($subQ) use ($search) {
                        $subQ->where('did_number', 'like', "%{$search}%");
                    });
            });
        }

        $agents = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json($agents);
    }

    /**
     * Store a newly created agent (request status)
     */
    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'admin_voice_id' => 'required|exists:admin_voices,id',
            'name' => 'required|string|max:255',
            'script' => 'required|string',
            'quantity' => 'integer|min:0',
        ]);

        $agent = CompanyAgent::create([
            'company_id' => $companyId,
            'admin_voice_id' => $validated['admin_voice_id'],
            'name' => $validated['name'],
            'script' => $validated['script'],
            'quantity' => $validated['quantity'] ?? 0,
            'status' => 'request', // Initial status
            'start_date' => now(),
            'price_per_min' => 0, // Will be set by admin
        ]);

        return response()->json($agent->load(['adminVoice']), 201);
    }

    /**
     * Display the specified agent
     */
    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $agent = CompanyAgent::where('company_id', $companyId)
            ->where('id', $id)
            ->with(['did', 'adminVoice', 'invoiceItems.invoice'])
            ->firstOrFail();

        return response()->json($agent);
    }

    /**
     * Update the specified agent
     */
    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $agent = CompanyAgent::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        // Only allow updates if status is request or inactive
        if (!in_array($agent->status, ['request', 'inactive'])) {
            return response()->json([
                'message' => 'Cannot update agent in current status'
            ], 403);
        }

        $validated = $request->validate([
            'admin_voice_id' => 'exists:admin_voices,id',
            'name' => 'string|max:255',
            'script' => 'string',
            'quantity' => 'integer|min:0',
        ]);

        $agent->update($validated);

        return response()->json($agent->load(['adminVoice']));
    }

    /**
     * Remove the specified agent (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $agent = CompanyAgent::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        // Only allow deletion if status is request or inactive
        if (!in_array($agent->status, ['request', 'inactive'])) {
            return response()->json([
                'message' => 'Cannot delete agent in current status'
            ], 403);
        }

        $agent->delete();

        return response()->json(['message' => 'Agent deleted successfully']);
    }

    /**
     * Get available voices for selection
     */
    public function availableVoices()
    {
        $voices = AdminVoice::where('status', 'active')->get();
        return response()->json($voices);
    }
}
