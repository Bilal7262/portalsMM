<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Did;
use Illuminate\Http\Request;

class DidController extends Controller
{
    public function index(Request $request)
    {
        $query = Did::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('did_number', 'like', '%' . $request->search . '%');
        }

        $dids = $query->with('companyDids.company')->latest()->paginate(20);

        return response()->json($dids);
    }

    public function store(Request $request)
    {
        $request->validate([
            'did_number' => 'required|string|unique:dids,did_number',
            'status' => 'required|in:available,assigned,maintenance',
        ]);

        $did = Did::create($request->all());

        return response()->json([
            'message' => 'DID created successfully',
            'did' => $did
        ], 201);
    }

    public function show($id)
    {
        $did = Did::with('companyDids.company')->findOrFail($id);
        return response()->json($did);
    }

    public function update(Request $request, $id)
    {
        $did = Did::findOrFail($id);

        $request->validate([
            'did_number' => 'sometimes|string|unique:dids,did_number,' . $did->id,
            'status' => 'sometimes|in:available,assigned,maintenance',
        ]);

        $did->update($request->all());

        return response()->json([
            'message' => 'DID updated successfully',
            'did' => $did
        ]);
    }

    public function destroy($id)
    {
        $did = Did::findOrFail($id);
        $did->delete();

        return response()->json(['message' => 'DID deleted successfully']);
    }
}
