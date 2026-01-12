<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDidInvoice;
use Illuminate\Http\Request;

class CompanyInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = CompanyDidInvoice::whereHas('companyDid', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->with('companyDid.did');

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'Draft');
        }

        // Search by Invoice ID or Amount?
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            // Assuming search is for ID for now, stripping 'INV-' if present
            $searchId = str_replace('INV-', '', $search);
            if (is_numeric($searchId)) {
                $query->where('id', 'like', "%{$searchId}%");
            }
        }

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['id', 'effective_from', 'created_at', 'total_minutes_consumption', 'billed_amount', 'status'];

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
        $invoice = CompanyDidInvoice::whereHas('companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', $id)->with(['companyDid.did', 'calls'])->firstOrFail();

        return response()->json($invoice);
    }

    public function download(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $invoice = CompanyDidInvoice::whereHas('companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', $id)->with(['companyDid.did', 'calls'])->firstOrFail();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"invoice_{$invoice->id}.csv\"",
        ];

        $callback = function () use ($invoice) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice ID', $invoice->id]);
            fputcsv($file, ['DID Number', $invoice->companyDid->did->did_number]);
            fputcsv($file, ['Billing Period', $invoice->effective_from . ' to ' . $invoice->effective_to]);
            fputcsv($file, ['Total Minutes', $invoice->total_minutes_consumption]);
            fputcsv($file, ['Billed Amount', '$' . number_format($invoice->billed_amount, 2)]);
            fputcsv($file, []);
            fputcsv($file, ['Call ID', 'Date', 'User Phone', 'Duration (Sec)', 'Disposition']);

            foreach ($invoice->calls as $call) {
                fputcsv($file, [
                    $call->id,
                    $call->created_at,
                    $call->user_phone,
                    $call->duration,
                    $call->disposition
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function getInvoiceCalls(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        // ensure invoice exists and belongs to company
        $invoice = CompanyDidInvoice::whereHas('companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', $id)->firstOrFail();

        $query = \App\Models\Call::where('company_did_invoice_id', $id)
            ->with(['invoice.companyDid.did']);

        // Apply Filters
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            $query->where(function ($q) use ($search) {
                $q->where('user_phone', 'like', "%{$search}%")
                    ->orWhereHas('invoice.companyDid.did', function ($subQ) use ($search) {
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
