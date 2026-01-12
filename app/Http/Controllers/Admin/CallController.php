<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function index(Request $request)
    {
        $query = Call::with(['invoiceItem.agent.company', 'invoiceItem.agent.did']);

        if ($request->filled('company_id')) {
            $query->whereHas('invoiceItem.agent', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        if ($request->filled('agent_id')) {
            $query->whereHas('invoiceItem', function ($q) use ($request) {
                $q->where('company_agent_id', $request->agent_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_phone', 'like', "%{$search}%")
                    ->orWhere('disposition', 'like', "%{$search}%")
                    ->orWhere('session_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        $calls = $query->latest()->paginate(20);

        return response()->json($calls);
    }

    public function show($id)
    {
        $call = Call::with([
            'invoiceItem.agent.company',
            'invoiceItem.agent.did',
            'invoiceItem.invoice',
            'messages'
        ])->findOrFail($id);
        
        return response()->json($call);
    }
}
