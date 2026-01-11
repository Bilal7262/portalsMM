<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $companies = $query->latest()->paginate(20);

        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies',
            'phone' => 'required|string|unique:companies',
            'status' => 'required|in:active,inactive,pending,suspended',
            'password' => 'required|string|min:8',
        ]);

        $company = Company::create([
            'business_name' => $request->business_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
        ]);

        // Create initial admin user for company
        $company->users()->create([
            'name' => 'Admin',
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Company and admin user created successfully',
            'company' => $company->load('users')
        ], 201);
    }

    public function show($id)
    {
        $company = Company::with(['users', 'dids.did'])->findOrFail($id);
        return response()->json($company);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:companies,email,' . $company->id,
            'phone' => 'sometimes|string|unique:companies,phone,' . $company->id,
            'status' => 'sometimes|in:active,inactive,pending,suspended',
        ]);

        $company->update($request->all());

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company
        ]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}
