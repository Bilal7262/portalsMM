<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\Request;
use App\Models\CompanyActivityLog;

class CompanyCallController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        // Calls belong to invoices which belong to company_dids which belong to company
        // Or we might have improved relationship in future. For now, query via nested relation
        $calls = Call::whereHas('invoice.companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->latest()->paginate(20);

        return response()->json($calls);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $call = Call::whereHas('invoice.companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', $id)->firstOrFail();

        return response()->json($call);
    }

    public function addFeedback(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $call = Call::whereHas('invoice.companyDid', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', $id)->firstOrFail();

        $request->validate([
            'company_rating' => 'required|integer|min:1|max:10',
            'company_feedback' => 'nullable|string',
        ]);

        $call->update([
            'company_rating' => $request->company_rating,
            'company_feedback' => $request->company_feedback,
        ]);

        CompanyActivityLog::create([
            'company_id' => $companyId,
            'company_user_id' => $request->user()->id,
            'activity_type' => 'CALL_FEEDBACK',
            'activity_details' => "Rated call {$call->id} with {$request->company_rating} stars.",
        ]);

        return response()->json(['message' => 'Feedback submitted successfully', 'call' => $call]);
    }
}
