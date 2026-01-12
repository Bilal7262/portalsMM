<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyAgentInvoice;
use App\Models\Call;
use Illuminate\Http\Request;

class CompanyInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = CompanyAgentInvoice::where('company_id', $companyId)
            ->with(['items.agent.did']);

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'Draft');
        }

        // Search by Invoice Number
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['id', 'invoice_number', 'effective_from', 'created_at', 'total_amount', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->latest();
        }

        $invoices = $query->paginate($request->input('per_page', 10));

        return response()->json($invoices);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $invoice = CompanyAgentInvoice::where('company_id', $companyId)
            ->where('id', $id)
            ->with(['items.agent.did'])
            ->firstOrFail();

        return response()->json($invoice);
    }

    public function download(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $invoice = CompanyAgentInvoice::where('company_id', $companyId)
            ->where('id', $id)
            ->with(['items.agent.did', 'items.calls'])
            ->firstOrFail();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"invoice_{$invoice->invoice_number}.csv\"",
        ];

        $callback = function () use ($invoice) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice Number', $invoice->invoice_number]);
            fputcsv($file, ['Billing Period', $invoice->effective_from . ' to ' . $invoice->effective_to]);
            fputcsv($file, ['Total Amount', '$' . number_format($invoice->total_amount, 2)]);
            fputcsv($file, []);
            
            // Itemized Agent Summary
            fputcsv($file, ['Agent Summary']);
            fputcsv($file, ['Agent', 'DID', 'Minutes', 'Rate', 'Subtotal']);
            foreach ($invoice->items as $item) {
                fputcsv($file, [
                    $item->agent->name,
                    $item->agent->did->did_number ?? 'N/A',
                    $item->total_minutes,
                    '$' . $item->rate_per_min,
                    '$' . $item->subtotal
                ]);
            }
            
            fputcsv($file, []);
            fputcsv($file, ['Call Details']);
            fputcsv($file, ['Agent', 'Date', 'User Phone', 'Duration (Sec)', 'Disposition']);

            foreach ($invoice->items as $item) {
                foreach ($item->calls as $call) {
                    fputcsv($file, [
                        $item->agent->name,
                        $call->created_at,
                        $call->user_phone,
                        $call->duration,
                        $call->disposition
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getInvoiceCalls(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        // Ensure invoice exists and belongs to company
        $invoice = CompanyAgentInvoice::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        // Query calls through invoice items
        $query = Call::whereHas('invoiceItem', function($q) use ($id) {
            $q->where('company_agent_invoice_id', $id);
        })->with(['invoiceItem.agent.did']);

        // Apply Filters
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            $query->where(function ($q) use ($search) {
                $q->where('user_phone', 'like', "%{$search}%")
                    ->orWhereHas('invoiceItem.agent.did', function ($subQ) use ($search) {
                        $subQ->where('did_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        // Apply Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['created_at', 'duration', 'company_rating', 'ai_rating'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->latest();
        }

        $calls = $query->paginate($request->input('per_page', 20));

        return response()->json($calls);
    }
}
