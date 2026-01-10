<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDidInvoice;
use Illuminate\Http\Request;

class CompanyInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        // Invoices are linked to company_dids, so we need to filter where company_did belongs to company
        $invoices = CompanyDidInvoice::whereHas('companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->with('companyDid.did')->latest()->get();

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
}
