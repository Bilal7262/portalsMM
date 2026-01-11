<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDid;
use Illuminate\Http\Request;

class CompanyDidController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $dids = CompanyDid::where('company_id', $companyId)->with('did')->get();
        return response()->json($dids);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $did = CompanyDid::where('company_id', $companyId)->where('id', $id)->with('did')->firstOrFail();
        return response()->json($did);
    }
}
