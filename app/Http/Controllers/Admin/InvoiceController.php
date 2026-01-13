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
            ->with(['company', 'items.agent.did'])
            ->withSum('items', 'total_minutes');

        // Optional filters - use filled() instead of has() to ignore empty strings
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Month filter (1-12)
        if ($request->filled('month')) {
            $query->whereMonth('effective_from', $request->month);
        }

        // Year filter
        if ($request->filled('year')) {
            $query->whereYear('effective_from', $request->year);
        }

        if ($request->filled('search')) {
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

    public function getInvoiceItems($id)
    {
        $invoice = CompanyAgentInvoice::with('company')->findOrFail($id);

        $items = \App\Models\CompanyAgentInvoiceItem::where('company_agent_invoice_id', $id)
            ->with(['agent.did', 'agent.company'])
            ->withCount([
                'calls as total_calls',
                'calls as total_sales' => function ($query) {
                    $query->where('disposition', 'SALE');
                }
            ])
            ->get();

        return response()->json([
            'invoice' => $invoice,
            'items' => $items
        ]);
    }

    public function getItemCalls($itemId, Request $request)
    {
        $item = \App\Models\CompanyAgentInvoiceItem::with(['agent.did', 'agent.company', 'invoice'])
            ->findOrFail($itemId);

        $query = \App\Models\Call::where('company_agent_invoice_item_id', $itemId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_phone', 'like', "%{$search}%")
                    ->orWhere('disposition', 'like', "%{$search}%")
                    ->orWhere('ai_feedback', 'like', "%{$search}%");
            });
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        if ($request->filled('has_rating') && $request->has_rating == 'true') {
            $query->where(function ($q) {
                $q->whereNotNull('ai_rating')
                    ->orWhereNotNull('company_rating');
            });
        }

        if ($request->filled('low_rating') && $request->low_rating == 'true') {
            $query->where(function ($q) {
                $q->where('ai_rating', '<', 3)
                    ->orWhere('company_rating', '<', 3);
            });
        }

        $calls = $query->latest()->paginate(50);

        return response()->json([
            'item' => $item,
            'calls' => $calls
        ]);
    }

    public function getCallMessages($callId)
    {
        $call = \App\Models\Call::findOrFail($callId);
        $messages = $call->messages()->orderBy('id')->get();

        return response()->json($messages);
    }

    public function getCallDetails($callId)
    {
        $call = \App\Models\Call::with(['invoiceItem.agent.did', 'invoiceItem.agent.company'])
            ->findOrFail($callId);

        return response()->json($call);
    }
}
