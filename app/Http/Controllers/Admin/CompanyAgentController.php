<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyAgent;
use App\Models\Did;
use App\Models\AdminVoice;
use Illuminate\Http\Request;

class CompanyAgentController extends Controller
{
    public function index(Request $request)
    {
        $query = CompanyAgent::with(['company', 'did', 'adminVoice']);

        // Optional filters
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('did', function ($subQ) use ($search) {
                        $subQ->where('did_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('company', function ($subQ) use ($search) {
                        $subQ->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        $agents = $query->latest()->paginate(50);

        return response()->json($agents);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'did_id' => 'required|exists:dids,id',
            'admin_voice_id' => 'required|exists:admin_voices,id',
            'name' => 'required|string|max:255',
            'script' => 'nullable|string',
            'quantity' => 'integer|min:0',
            'price_per_min' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'in:request,training,active,maintenance,inactive',
        ]);

        $agent = CompanyAgent::create([
            'company_id' => $request->company_id,
            'did_id' => $request->did_id,
            'admin_voice_id' => $request->admin_voice_id,
            'name' => $request->name,
            'script' => $request->script,
            'quantity' => $request->quantity ?? 0,
            'price_per_min' => $request->price_per_min,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'request',
        ]);

        // Update DID status to assigned
        $did = Did::find($request->did_id);
        if ($did) {
            $did->update(['status' => 'assigned']);
        }

        return response()->json([
            'message' => 'Agent assigned to company successfully',
            'agent' => $agent->load(['company', 'did', 'adminVoice'])
        ], 201);
    }

    public function show($id)
    {
        $agent = CompanyAgent::with(['company', 'did', 'adminVoice', 'invoiceItems.invoice'])->findOrFail($id);
        return response()->json($agent);
    }

    public function update(Request $request, $id)
    {
        $agent = CompanyAgent::findOrFail($id);

        $request->validate([
            'admin_voice_id' => 'sometimes|exists:admin_voices,id',
            'name' => 'sometimes|string|max:255',
            'script' => 'nullable|string',
            'quantity' => 'sometimes|integer|min:0',
            'price_per_min' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'sometimes|in:request,training,active,maintenance,inactive',
        ]);

        $agent->update($request->all());

        return response()->json([
            'message' => 'Agent updated successfully',
            'agent' => $agent->load(['company', 'did', 'adminVoice'])
        ]);
    }

    public function destroy($id)
    {
        $agent = CompanyAgent::findOrFail($id);

        // Release DID
        $did = Did::find($agent->did_id);
        if ($did) {
            $did->update(['status' => 'available']);
        }

        $agent->delete();

        return response()->json(['message' => 'Agent removed successfully']);
    }

    /**
     * Approve agent request (change status from request to training/active)
     */
    public function approve(Request $request, $id)
    {
        $agent = CompanyAgent::findOrFail($id);

        $request->validate([
            'status' => 'required|in:training,active',
        ]);

        $agent->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Agent approved successfully',
            'agent' => $agent
        ]);
    }
}
