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
        // Invoices are linked to company_dids, so we need to filter where company_did belongs to company
        $invoices = CompanyDidInvoice::whereHas('companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('status', '!=', 'Draft')->with('companyDid.did')->latest()->get();

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
}
