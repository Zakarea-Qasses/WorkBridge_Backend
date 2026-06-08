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

        $companies = Company::with(['user:id,name,email,status', 'skills'])
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
        $companies = Company::with(['user:id,name,email,status', 'skills'])
            ->where('is_verified', false)
            ->whereHas('user', fn ($query) => $query->whereIn('status', ['pending_review', 'under_review']))
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
                'title' => 'Company verified',
                'message' => 'Your company account has been verified by admin.',
            ]);
        }

        return response()->json([
            'message' => 'Company verified successfully.',
            'company' => $company->fresh()->load(['user:id,name,email,status', 'skills']),
        ]);
    }

    public function unverify(int $id)
    {
        $company = Company::with('user')->findOrFail($id);

        $company->update(['is_verified' => false]);

        if ($company->user) {
            $company->user->update(['status' => 'under_review']);
            $company->user->tokens()->delete();

            UserNotification::create([
                'user_id' => $company->user->id,
                'type' => 'company_unverified',
                'title' => 'Company verification removed',
                'message' => 'Your company account has been moved back under review by admin.',
            ]);
        }

        return response()->json([
            'message' => 'Company verification removed successfully.',
            'company' => $company->fresh()->load(['user:id,name,email,status', 'skills']),
        ]);
    }
}
