<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * List all non-admin users with their leave balances.
     */
    public function index(): JsonResponse
    {
        $users = User::where('role', 'user')
            ->with(['leaveBalances.leaveType'])
            ->get()
            ->map(function (User $user) {
                return $this->formatUser($user);
            });

        $totalUsers = $users->count();
        $maxUsers   = 2;

        return response()->json([
            'data' => [
                'users'       => $users,
                'total_users' => $totalUsers,
                'max_users'   => $maxUsers,
                'slots_available' => max(0, $maxUsers - $totalUsers),
            ],
        ]);
    }

    /**
     * Create a new user (max 2 users allowed).
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $userCount = User::where('role', 'user')->count();
        if ($userCount >= 2) {
            return response()->json([
                'message' => 'Tidak dapat membuat user baru. Sudah mencapai batas maksimal 2 user.',
                'errors'  => ['limit' => ['Batas maksimal 2 user telah tercapai.']],
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => 'user',
        ]);

        // Auto-assign leave balances for all leave types
        $currentYear = now()->year;
        $leaveTypes  = LeaveType::all();

        foreach ($leaveTypes as $leaveType) {
            LeaveBalance::create([
                'user_id'       => $user->id,
                'leave_type_id' => $leaveType->id,
                'year'          => $currentYear,
                'total_quota'   => $leaveType->default_quota,
                'used'          => 0,
            ]);
        }

        $user->load('leaveBalances.leaveType');

        return response()->json([
            'message' => "User {$user->name} berhasil dibuat dengan balance cuti penuh.",
            'data'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * Update user password.
     */
    public function updatePassword(UpdateUserPasswordRequest $request, User $user): JsonResponse
    {
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Tidak dapat mengubah password admin melalui endpoint ini.',
            ], 403);
        }

        $user->update(['password' => $request->password]);

        return response()->json([
            'message' => "Password user {$user->name} berhasil diperbarui.",
        ]);
    }

    private function formatUser(User $user): array
    {
        $year     = now()->year;
        $balances = $user->leaveBalances
            ->where('year', $year)
            ->map(fn(LeaveBalance $b) => [
                'leave_type_id'   => $b->leave_type_id,
                'leave_type_name' => $b->leaveType->name,
                'total_quota'     => $b->total_quota,
                'used'            => $b->used,
                'remaining'       => $b->getRemaining(),
            ])->values();

        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'role'     => $user->role,
            'balances' => $balances,
        ];
    }
}
