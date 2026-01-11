<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyActivityLog;
use Illuminate\Http\Request;

class CompanyActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        
        $logs = CompanyActivityLog::where('company_id', $companyId)
            ->with('companyUser:id,name,email')
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
