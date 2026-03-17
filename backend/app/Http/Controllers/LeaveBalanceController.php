<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    /**
     * Get the authenticated user's leave balances for the current year.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = now()->year;

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get()
            ->map(fn(LeaveBalance $b) => [
                'leave_type_id'   => $b->leave_type_id,
                'leave_type_name' => $b->leaveType->name,
                'year'            => $b->year,
                'total_quota'     => $b->total_quota,
                'used'            => $b->used,
                'remaining'       => $b->getRemaining(),
            ]);

        // Summary counts
        $pending  = LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count();
        $approved = LeaveRequest::where('user_id', $user->id)->where('status', 'approved')->whereYear('responded_at', $year)->count();
        $rejected = LeaveRequest::where('user_id', $user->id)->where('status', 'rejected')->whereYear('responded_at', $year)->count();

        return response()->json([
            'data' => [
                'year'     => $year,
                'balances' => $balances,
                'summary'  => [
                    'pending'  => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected,
                ],
            ],
        ]);
    }
}
