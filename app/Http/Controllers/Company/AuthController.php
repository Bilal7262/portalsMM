<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:companies',
            'phone' => 'required|string|unique:companies',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 1. Create Company
        $company = Company::create([
            'business_name' => $request->business_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'verify_email' => false, // Needs OTP verification
            'verify_phone' => false, // Needs manual verification
        ]);

        // 2. Create Default Admin User for Company
        $user = CompanyUser::create([
            'company_id' => $company->id,
            'name' => 'Admin', // Default name
            'email' => $request->email, // Same as company email initially
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        // 3. Assign Company Admin Role
        $adminRole = Role::where('name', 'company-admin')->first();
        if ($adminRole) {
            $user->addRole($adminRole);
        }

        // TODO: Send OTP to email
        // For now, logging it or assuming '123456'
        \Illuminate\Support\Facades\Log::info("OTP for {$company->email}: 123456");

        return response()->json([
            'message' => 'Company registered successfully. Please verify your email.',
            'company' => $company,
        ], 201);
    }

    public function verifyEmail(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:companies,email',
            'otp' => 'required'
        ]);

        // Stub verification
        if ($request->otp !== '123456') {
             throw ValidationException::withMessages([
                'otp' => ['Invalid OTP'],
            ]);
        }

        $company = Company::where('email', $request->email)->first();
        $company->update(['verify_email' => true]);

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = CompanyUser::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive.'],
            ]);
        }

        // Check Company Status if needed
        if ($user->company && $user->company->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your company account is not active.'],
            ]);
        }

        $token = $user->createToken('company_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles', 'company'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'company'));
    }
}
