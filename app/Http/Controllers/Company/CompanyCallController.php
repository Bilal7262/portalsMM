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

        $query = Call::whereHas('invoiceItem.agent', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        });

        // Filter by current month if requested
        if ($request->has('current_month')) {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        }

        $calls = $query->latest()->paginate(20);

        return response()->json($calls);
    }

    public function currentMonth(Request $request)
    {
        $companyId = $request->user()->company_id;
        $now = now();

        // Base query for company calls
        $query = Call::whereHas('invoiceItem.agent', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->with(['invoiceItem.agent.did']);

        // Calculate stats (before filtering to show total for month)
        // Optimization: Cache or aggregate separately if slow
        $statsQuery = clone $query;
        $totalMinutes = ceil($statsQuery->sum('duration') / 60);
        // Approximate amount
        $totalAmount = $totalMinutes * 0.05;

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

        return response()->json([
            'period' => $now->format('F Y'),
            'minutes' => $totalMinutes,
            'amount' => round($totalAmount, 2),
            'calls' => $calls
        ]);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $call = Call::whereHas('invoiceItem.agent', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->with('messages')->where('id', $id)->firstOrFail();

        return response()->json($call);
    }

    public function addFeedback(Request $request, $id)
    {
        $companyId = $request->user()->company_id;
        $call = Call::whereHas('invoiceItem.agent', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', $id)->firstOrFail();

        $request->validate([
            'company_rating' => 'required|numeric|min:0.5|max:5',
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
