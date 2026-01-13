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

        $items = $invoice->items()
            ->with(['agent.did', 'agent.company'])
            ->get();

        return response()->json([
            'invoice' => $invoice,
            'items' => $items
        ]);
    }

    public function getItemCalls($itemId)
    {
        $item = \App\Models\CompanyAgentInvoiceItem::with(['agent.did', 'agent.company', 'invoice'])
            ->findOrFail($itemId);

        $calls = \App\Models\Call::where('company_agent_invoice_item_id', $itemId)
            ->latest()
            ->paginate(50);

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
