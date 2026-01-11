<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\CompanyActivityLog;

class CompanyUserController extends Controller
{
    public function index(Request $request)
    {
        // Get users belonging to the authenticated user's company
        $companyId = $request->user()->company_id;
        $users = CompanyUser::where('company_id', $companyId)->with('roles')->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:company_users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:manager,user', // Use simple role names from input
        ]);

        $user = CompanyUser::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        // Assign role - map 'manager' -> 'company-manager', etc.
        $roleName = 'company-' . $request->role;
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->addRole($role);
        }

        CompanyActivityLog::create([
            'company_id' => $companyId,
            'company_user_id' => $request->user()->id,
            'activity_type' => 'USER_CREATED',
            'activity_details' => "Created user: {$user->email} with role: {$request->role}",
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles')
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $user = CompanyUser::where('company_id', $companyId)->where('id', $id)->with('roles')->firstOrFail();
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $user = CompanyUser::where('company_id', $companyId)->where('id', $id)->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:company_users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'role' => 'sometimes|in:manager,user',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $user->update($request->only(['name', 'email', 'status']));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        if ($request->filled('role')) {
            $user->syncRoles(['company-' . $request->role]);
        }

        CompanyActivityLog::create([
            'company_id' => $companyId,
            'company_user_id' => $request->user()->id,
            'activity_type' => 'USER_UPDATED',
            'activity_details' => "Updated user: {$user->email}",
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $user = CompanyUser::where('company_id', $companyId)->where('id', $id)->firstOrFail();

        // Prevent deleting self
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 403);
        }

        $user->delete();

        CompanyActivityLog::create([
            'company_id' => $companyId,
            'company_user_id' => $request->user()->id,
            'activity_type' => 'USER_DELETED',
            'activity_details' => "Deleted user: {$user->email}",
        ]);

        return response()->json(['message' => 'User deleted successfully']);
    }
}
