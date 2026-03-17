<?php

namespace App\Http\Controllers;

use App\Http\Requests\RespondLeaveRequestRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    /**
     * Admin: list all leave requests (pending first, then history).
     * User: list own leave requests.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = LeaveRequest::with(['user', 'leaveType', 'respondedByUser'])
            ->withTrashed(); // include soft-deleted for history

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        // Split into pending and history
        $pending = $requests->whereNull('deleted_at')
            ->where('status', 'pending')
            ->values();

        $history = $requests->where(function ($r) {
            return $r->status !== 'pending' || $r->deleted_at !== null;
        })->values();

        return response()->json([
            'data' => [
                'pending'  => $pending->map(fn($r) => $this->formatRequest($r)),
                'history'  => $history->map(fn($r) => $this->formatRequest($r)),
                'summary'  => [
                    'pending'  => $requests->whereNull('deleted_at')->where('status', 'pending')->count(),
                    'approved' => $requests->whereNull('deleted_at')->where('status', 'approved')->count(),
                    'rejected' => $requests->whereNull('deleted_at')->where('status', 'rejected')->count(),
                ],
            ],
        ]);
    }

    /**
     * User: submit a new leave request.
     */
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $user     = $request->user();
        $year     = now()->year;
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate   = \Carbon\Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Check leave balance
        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return response()->json([
                'message' => 'Kuota cuti tidak ditemukan untuk tahun ini.',
                'errors'  => ['leave_type_id' => ['Kuota cuti tidak tersedia.']],
            ], 422);
        }

        $remaining = $balance->getRemaining();
        if ($totalDays > $remaining) {
            return response()->json([
                'message' => "Kuota cuti tidak mencukupi. Sisa {$remaining} hari, mengajukan {$totalDays} hari.",
                'errors'  => ['total_days' => ["Kuota tidak cukup — sisa {$remaining} hari, mengajukan {$totalDays} hari."]],
            ], 422);
        }

        // Check overlap with pending/approved requests
        $overlap = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($q2) use ($request) {
                        $q2->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })
            ->first();

        if ($overlap) {
            $overlapStart = $overlap->start_date->format('d M Y');
            $overlapEnd   = $overlap->end_date->format('d M Y');
            return response()->json([
                'message' => "Tanggal bentrok dengan request {$overlapStart} – {$overlapEnd} (status: {$overlap->status}).",
                'errors'  => ['start_date' => ["Overlap terdeteksi — bentrok dengan request {$overlapStart} – {$overlapEnd} ({$overlap->status})."]],
            ], 422);
        }

        $leaveRequest = LeaveRequest::create([
            'user_id'       => $user->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'total_days'    => $totalDays,
            'reason'        => $request->reason,
            'status'        => 'pending',
        ]);

        $leaveRequest->load(['leaveType', 'user']);

        return response()->json([
            'message' => 'Permohonan cuti berhasil diajukan.',
            'data'    => $this->formatRequest($leaveRequest),
        ], 201);
    }

    /**
     * Admin: approve or reject a leave request.
     */
    public function respond(RespondLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $admin = $request->user();

        if (!$leaveRequest->isPending()) {
            return response()->json([
                'message' => "Request tidak bisa direspon — status saat ini adalah '{$leaveRequest->status}', bukan 'pending'.",
            ], 422);
        }

        $action = $request->action; // 'approve' or 'reject'

        if ($action === 'approve') {
            $year    = now()->year;
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                return response()->json([
                    'message' => 'Balance cuti tidak ditemukan untuk user ini.',
                ], 422);
            }

            $remaining = $balance->getRemaining();
            if ($leaveRequest->total_days > $remaining) {
                return response()->json([
                    'message' => "Kuota cuti user tidak mencukupi. Sisa {$remaining} hari, request {$leaveRequest->total_days} hari.",
                ], 422);
            }

            $balance->increment('used', $leaveRequest->total_days);
            $leaveRequest->update([
                'status'       => 'approved',
                'responded_by' => $admin->id,
                'admin_notes'  => $request->admin_notes,
                'responded_at' => now(),
            ]);

            $message = "Request cuti {$leaveRequest->user->name} berhasil diapprove.";
        } else {
            $leaveRequest->update([
                'status'       => 'rejected',
                'responded_by' => $admin->id,
                'admin_notes'  => $request->admin_notes,
                'responded_at' => now(),
            ]);

            $message = "Request cuti {$leaveRequest->user->name} ditolak.";
        }

        $leaveRequest->load(['user', 'leaveType', 'respondedByUser']);

        return response()->json([
            'message' => $message,
            'data'    => $this->formatRequest($leaveRequest),
        ]);
    }

    /**
     * User: cancel a pending leave request.
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $user = $request->user();

        if ($leaveRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke request ini.'], 403);
        }

        if (!$leaveRequest->isPending()) {
            return response()->json([
                'message' => "Hanya request dengan status 'pending' yang bisa dibatalkan. Status saat ini: '{$leaveRequest->status}'.",
            ], 422);
        }

        $leaveRequest->update([
            'status'      => 'cancelled',
            'admin_notes' => 'Dibatalkan oleh user',
        ]);

        return response()->json(['message' => 'Permohonan cuti berhasil dibatalkan.']);
    }

    /**
     * Soft delete a leave request.
     * Admin: any final request.
     * User: own cancelled/rejected request.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user         = $request->user();
        $leaveRequest = LeaveRequest::withTrashed()->findOrFail($id);

        if ($leaveRequest->trashed()) {
            return response()->json(['message' => 'Request sudah dihapus sebelumnya.'], 422);
        }

        if ($leaveRequest->isPending()) {
            return response()->json([
                'message' => "Request yang masih 'pending' tidak bisa dihapus. Batalkan terlebih dahulu.",
            ], 422);
        }

        if (!$user->isAdmin()) {
            if ($leaveRequest->user_id !== $user->id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke request ini.'], 403);
            }
            if (!in_array($leaveRequest->status, ['cancelled', 'rejected'])) {
                return response()->json([
                    'message' => 'User hanya bisa menghapus request dengan status cancelled atau rejected.',
                ], 422);
            }
        }

        $leaveRequest->update(['deleted_by' => $user->id]);
        $leaveRequest->delete(); // SoftDelete

        return response()->json(['message' => 'Request berhasil dihapus.']);
    }

    private function formatRequest(LeaveRequest $r): array
    {
        return [
            'id'             => $r->id,
            'user_id'        => $r->user_id,
            'user_name'      => $r->user?->name,
            'leave_type_id'  => $r->leave_type_id,
            'leave_type_name'=> $r->leaveType?->name,
            'start_date'     => $r->start_date?->format('Y-m-d'),
            'end_date'       => $r->end_date?->format('Y-m-d'),
            'total_days'     => $r->total_days,
            'reason'         => $r->reason,
            'status'         => $r->status,
            'admin_notes'    => $r->admin_notes,
            'responded_by'   => $r->responded_by,
            'responded_by_name' => $r->respondedByUser?->name,
            'responded_at'   => $r->responded_at?->toISOString(),
            'created_at'     => $r->created_at?->toISOString(),
            'deleted_at'     => $r->deleted_at?->toISOString(),
            'deleted_by'     => $r->deleted_by,
        ];
    }
}
