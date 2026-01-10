<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function roles()
    {
        $roles = Role::where('name', 'like', 'admin-%')->get();
        return response()->json($roles);
    }

    public function index()
    {
        $admins = Admin::with('roles')->get();
        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        $role = Role::find($request->role_id);
        $admin->addRole($role);

        AdminActivityLog::create([
            'admin_id' => auth('admin')->id(),
            'activity_type' => 'create_admin',
            'activity_details' => "Created admin user: {$admin->name} ({$admin->email})",
            'activity_date' => now(),
        ]);

        return response()->json($admin->load('roles'), 201);
    }

    public function show(Admin $admin)
    {
        return response()->json($admin->load('roles'));
    }

    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins')->ignore($admin->id),
            ],
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status,
        ]);

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
            $admin->save();
        }

        $role = Role::find($request->role_id);
        $admin->syncRoles([$role]);

        AdminActivityLog::create([
            'admin_id' => auth('admin')->id(),
            'activity_type' => 'update_admin',
            'activity_details' => "Updated admin user: {$admin->name} (ID: {$admin->id})",
            'activity_date' => now(),
        ]);

        return response()->json($admin->load('roles'));
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id === auth()->id()) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

        $admin->delete();

        AdminActivityLog::create([
            'admin_id' => auth()->id(),
            'activity_type' => 'delete_admin',
            'activity_details' => "Deleted admin user (ID: {$admin->id})",
            'activity_date' => now(),
        ]);

        return response()->json(['message' => 'Admin deleted successfully.']);
    }
}
