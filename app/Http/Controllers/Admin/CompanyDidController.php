<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyDid;
use App\Models\Did;
use Illuminate\Http\Request;

class CompanyDidController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'did_id' => 'required|exists:dids,id',
            'price_per_min' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $assignment = CompanyDid::create([
            'company_id' => $request->company_id,
            'did_id' => $request->did_id,
            'price_per_min' => $request->price_per_min,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'active',
        ]);

        // Update DID status to assigned
        $did = Did::find($request->did_id);
        if ($did) {
            $did->update(['status' => 'assigned']);
        }

        return response()->json([
            'message' => 'DID assigned to company successfully',
            'assignment' => $assignment
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $assignment = CompanyDid::findOrFail($id);

        $request->validate([
            'price_per_min' => 'sometimes|numeric',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $assignment->update($request->all());

        return response()->json([
            'message' => 'Assignment updated successfully',
            'assignment' => $assignment
        ]);
    }

    public function destroy($id)
    {
        $assignment = CompanyDid::findOrFail($id);

        // Release DID
        $did = Did::find($assignment->did_id);
        if ($did) {
            $did->update(['status' => 'available']);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment removed successfully']);
    }
}
