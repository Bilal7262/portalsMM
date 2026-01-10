<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function show(Request $request)
    {
        $company = $request->user()->company;
        return response()->json($company);
    }

    public function update(Request $request)
    {
        $company = $request->user()->company;

        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:companies,phone,' . $company->id,
            'business_address' => 'nullable|string',
        ]);

        $company->update($request->only([
            'business_name',
            'phone',
            'business_address',
        ]));

        return response()->json([
            'message' => 'Company profile updated successfully',
            'company' => $company
        ]);
    }
}
