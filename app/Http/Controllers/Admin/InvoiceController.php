<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyAgentInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = CompanyAgentInvoice::query()
            ->with(['company', 'items.agent.did']);

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($subQ) use ($search) {
                        $subQ->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->latest()->paginate(50);

        return response()->json($invoices);
    }

    public function show($id)
    {
        $invoice = CompanyAgentInvoice::with([
            'company',
            'items.agent.did',
            'items.agent.adminVoice',
            'items.calls'
        ])->findOrFail($id);
        
        return response()->json($invoice);
    }

    public function update(Request $request, $id)
    {
        $invoice = CompanyAgentInvoice::findOrFail($id);

        $request->validate([
            'status' => 'required|in:Draft,Finalized,Paid',
        ]);

        $invoice->update($request->only('status'));

        return response()->json([
            'message' => 'Invoice status updated successfully',
            'invoice' => $invoice
        ]);
    }
}
