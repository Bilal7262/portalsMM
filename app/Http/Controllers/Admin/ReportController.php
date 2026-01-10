<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Did;
use App\Models\CompanyDidInvoice;
use App\Models\Call;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('status', 'active')->count(),
            'total_dids' => Did::count(),
            'assigned_dids' => Did::where('status', 'assigned')->count(),
            'available_dids' => Did::where('status', 'available')->count(),
            'total_revenue' => CompanyDidInvoice::where('status', 'paid')->sum('billed_amount'),
            'total_calls' => Call::count(),
            'recent_calls' => Call::latest()->take(5)->get(), // Partial data for dashboard
        ];

        return response()->json($stats);
    }
}
