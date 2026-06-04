<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['personal', 'company'])],
            'status' => ['nullable', Rule::in(['pending_review', 'under_review', 'active', 'blocked'])],
        ]);

        $users = User::query()
            ->where('role', '!=', 'admin')
            ->with(['profile.skills', 'company.skills'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($data['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function reviewBoard()
    {
        return response()->json([
            'pending_review' => $this->usersByStatus('pending_review'),
            'under_review' => $this->usersByStatus('under_review'),
            'reviewed' => User::query()
                ->where('role', '!=', 'admin')
                ->whereIn('status', ['active', 'blocked'])
                ->with(['profile.skills', 'company.skills'])
                ->latest()
                ->get(),
            'counts' => [
                'pending_review' => User::where('role', '!=', 'admin')->where('status', 'pending_review')->count(),
                'under_review' => User::where('role', '!=', 'admin')->where('status', 'under_review')->count(),
                'active' => User::where('role', '!=', 'admin')->where('status', 'active')->count(),
                'blocked' => User::where('role', '!=', 'admin')->where('status', 'blocked')->count(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $user = User::where('role', '!=', 'admin')
            ->with(['profile.skills', 'company.skills'])
            ->findOrFail($id);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function markUnderReview(int $id)
    {
        $user = User::where('role', '!=', 'admin')->findOrFail($id);

        $user->update([
            'status' => 'under_review',
        ]);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'تم نقل الحساب إلى قسم تحت المراجعة',
            'user' => $user->load(['profile.skills', 'company.skills']),
        ]);
    }

    public function approve(int $id)
    {
        $user = User::where('role', '!=', 'admin')->findOrFail($id);

        $user->update([
            'status' => 'active',
        ]);

        if ($user->role === 'company' && $user->company) {
            $user->company->update([
                'is_verified' => true,
            ]);
        }

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'account_approved',
            'title' => 'تم قبول الحساب',
            'message' => 'تمت مراجعة حسابك وقبوله من قبل الإدارة.',
        ]);

        return response()->json([
            'message' => 'تم قبول الحساب بنجاح',
            'user' => $user->load(['profile.skills', 'company.skills']),
        ]);
    }

    public function block(int $id)
    {
        $user = User::where('role', '!=', 'admin')->findOrFail($id);

        $user->update([
            'status' => 'blocked',
        ]);

        $user->tokens()->delete();

        if ($user->role === 'company' && $user->company) {
            $user->company->update([
                'is_verified' => false,
            ]);
        }

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'account_blocked',
            'title' => 'تم حظر الحساب',
            'message' => 'تم حظر حسابك من قبل الإدارة.',
        ]);

        return response()->json([
            'message' => 'تم حظر الحساب بنجاح',
            'user' => $user->load(['profile.skills', 'company.skills']),
        ]);
    }

    public function destroy(int $id)
    {
        $user = User::where('role', '!=', 'admin')->findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'تم حذف الحساب بنجاح',
        ]);
    }

    private function usersByStatus(string $status)
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->where('status', $status)
            ->with(['profile.skills', 'company.skills'])
            ->latest()
            ->get();
    }
}
