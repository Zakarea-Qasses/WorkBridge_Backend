<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class AdminCompanyVerificationController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $companies = Company::with(['user:id,name,email,status', 'skills', 'governorate', 'city'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('company_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            })
            ->when(array_key_exists('is_verified', $data), fn ($query) => $query->where('is_verified', $data['is_verified']))
            ->latest()
            ->paginate(10);

        return response()->json([
            'companies' => $companies,
        ]);
    }

    public function pending()
    {
        $companies = Company::with(['user:id,name,email,status', 'skills', 'governorate', 'city'])
            ->where('is_verified', false)
            ->whereHas('user', fn ($query) => $query->whereIn('status', ['unactive', 'pending_review', 'under_review']))
            ->latest()
            ->get();

        return response()->json([
            'companies' => $companies,
        ]);
    }

    public function verify(int $id)
    {
        $company = Company::with('user')->findOrFail($id);

        $company->update(['is_verified' => true]);

        if ($company->user) {
            $company->user->update(['status' => 'active']);

            UserNotification::create([
                'user_id' => $company->user->id,
                'type' => 'company_verified',
                'title' => 'تم توثيق الشركة',
                'message' => 'تم توثيق حساب شركتك من قبل الإدارة.',
            ]);
        }

        return response()->json([
            'message' => 'تم توثيق الشركة بنجاح.',
            'company' => $company->fresh()->load(['user:id,name,email,status', 'skills', 'governorate', 'city']),
        ]);
    }

    public function unverify(int $id)
    {
        $company = Company::with('user')->findOrFail($id);

        $company->update(['is_verified' => false]);

        if ($company->user) {
            $company->user->update(['status' => 'unactive']);
            $company->user->tokens()->delete();

            UserNotification::create([
                'user_id' => $company->user->id,
                'type' => 'company_unverified',
                'title' => 'تم إلغاء توثيق الشركة',
                'message' => 'تم إلغاء توثيق حساب شركتك من قبل الإدارة.',
            ]);
        }

        return response()->json([
            'message' => 'تم إلغاء توثيق الشركة بنجاح.',
            'company' => $company->fresh()->load(['user:id,name,email,status', 'skills', 'governorate', 'city']),
        ]);
    }
}
