<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function index(Request $request)
    {
        $query = Call::with(['invoice.companyDid.company', 'invoice.companyDid.did']);

        if ($request->filled('company_id')) {
            $query->whereHas('invoice.companyDid', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->filled('search')) {
            $query->where('user_phone', 'like', '%' . $request->search . '%')
                ->orWhere('disposition', 'like', '%' . $request->search . '%');
        }

        $calls = $query->latest()->paginate(20);

        return response()->json($calls);
    }

    public function show($id)
    {
        $call = Call::with(['invoice.companyDid.company', 'invoice.companyDid.did'])->findOrFail($id);
        return response()->json($call);
    }
}
