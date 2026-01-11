<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyDidInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = CompanyDidInvoice::query()
            ->with(['companyDid.company', 'companyDid.did']);

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('company_id')) {
            $query->whereHas('companyDid', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        $invoices = $query->latest()->paginate(50);

        return response()->json($invoices);
    }

    public function show($id)
    {
        $invoice = CompanyDidInvoice::with(['companyDid.company', 'companyDid.did', 'calls'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function update(Request $request, $id)
    {
        $invoice = CompanyDidInvoice::findOrFail($id);

        $request->validate([
            'status' => 'required|in:draft,generated,sent,paid,cancelled',
            // Maybe handle payment details here
        ]);

        $invoice->update($request->only('status'));

        return response()->json([
            'message' => 'Invoice status updated successfully',
            'invoice' => $invoice
        ]);
    }
}
