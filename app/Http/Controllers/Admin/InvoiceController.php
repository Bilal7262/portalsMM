<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyDidInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = CompanyDidInvoice::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Support filtering by company?
        if ($request->filled('company_id')) {
            $query->whereHas('companyDid', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        $invoices = $query->with(['companyDid.company', 'companyDid.did'])->latest()->paginate(20);

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
