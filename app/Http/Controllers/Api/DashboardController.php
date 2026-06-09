<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contract;
use App\Models\JobPost;
use App\Models\Report;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\UserProject;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function personal(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'message' => 'Welcome to personal dashboard',
            'role' => $user->role,
            'user' => $user,
            'stats' => [
                'total_projects' => UserProject::where('user_id', $user->id)->count(),
                'active_projects' => UserProject::where('user_id', $user->id)->where('status', 'active')->count(),
                'active_contracts' => Contract::where(function ($query) use ($user) {
                    $query->where('client_id', $user->id)
                        ->orWhere('freelancer_id', $user->id);
                })->whereIn('status', ['funded', 'in_progress'])->count(),
                'wallet_balance' => (float) optional($user->wallet)->balance,
                'rating_avg' => round((float) Review::where('reviewed_user_id', $user->id)->avg('rating'), 2),
            ],
            'recent_projects' => $this->personalRecentProjects($user->id),
            'active_contracts' => $this->personalActiveContracts($user->id),
            'quick_actions' => [
                ['key' => 'create_project', 'label' => 'Create project'],
                ['key' => 'create_service', 'label' => 'Create service'],
                ['key' => 'view_wallet', 'label' => 'View wallet'],
            ],
        ], 200);
    }

    public function company(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'message' => 'Welcome to company dashboard',
            'role' => $user->role,
            'user' => $user,
        ], 200);
    }

    public function admin(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'message' => 'Welcome to admin dashboard',
            'role' => $user->role,
            'user' => $user,
            'stats' => [
                'total_users' => User::where('role', '!=', 'admin')->count(),
                'active_companies' => Company::where('is_verified', true)->count(),
                'open_disputes' => Report::where('target_type', 'contract')->where('status', 'pending')->count(),
                'platform_profit' => $this->platformProfit(),
            ],
            'company_verification_requests' => $this->companyVerificationRequests(),
            'content_needing_review' => $this->contentNeedingReview(),
            'dispute_alerts' => $this->disputeAlerts(),
            'charts' => [
                'users_growth' => $this->monthlyUsersGrowth(),
                'monthly_revenue' => $this->monthlyRevenue(),
            ],
        ], 200);
    }

    private function platformProfit(): float
    {
        $adminWallet = Wallet::where('type', 'admin')->first();

        if (! $adminWallet) {
            return 0;
        }

        return (float) $adminWallet->transactions()
            ->whereIn('type', ['admin_receive', 'commission', 'platform_commission'])
            ->where('direction', 'credit')
            ->sum('amount');
    }

    private function personalRecentProjects(int $userId)
    {
        return UserProject::with('category:id,name')
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->title,
                'budget' => (float) $project->budget,
                'duration_days' => $project->duration_days,
                'status' => $project->status ?? 'active',
                'category_name' => $project->category?->name,
                'created_at' => $project->created_at,
            ]);
    }

    private function personalActiveContracts(int $userId)
    {
        return Contract::with(['project:id,title', 'serviceRequest:id,title', 'client:id,name', 'freelancer:id,name'])
            ->where(function ($query) use ($userId) {
                $query->where('client_id', $userId)
                    ->orWhere('freelancer_id', $userId);
            })
            ->whereIn('status', ['pending', 'funded', 'in_progress', 'dispute'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($contract) => [
                'id' => $contract->id,
                'title' => $contract->project?->title ?? $contract->serviceRequest?->title,
                'amount' => (float) $contract->amount,
                'status' => $contract->status,
                'client_name' => $contract->client?->name,
                'freelancer_name' => $contract->freelancer?->name,
                'created_at' => $contract->created_at,
            ]);
    }

    private function companyVerificationRequests()
    {
        return Company::with(['user:id,name,email,status'])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($company) => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'owner_name' => $company->user?->name,
                'owner_email' => $company->user?->email,
                'status' => $company->is_verified ? 'verified' : 'under_review',
                'created_at' => $company->created_at,
            ]);
    }

    private function contentNeedingReview()
    {
        $projects = UserProject::with('user:id,name')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->title,
                'type' => 'freelance_project',
                'owner_name' => $project->user?->name,
                'status' => $project->status ?? 'active',
                'created_at' => $project->created_at,
            ]);

        $services = Service::with('user:id,name')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($service) => [
                'id' => $service->id,
                'title' => $service->title,
                'type' => 'freelance_service',
                'owner_name' => $service->user?->name,
                'status' => $service->status ?? 'active',
                'created_at' => $service->created_at,
            ]);

        $jobs = JobPost::with('company:id,company_name')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'type' => 'job_posting',
                'owner_name' => $job->company?->company_name,
                'status' => $job->status,
                'created_at' => $job->created_at,
            ]);

        return $projects
            ->merge($services)
            ->merge($jobs)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();
    }

    private function disputeAlerts()
    {
        return Report::with(['reporter:id,name', 'contract.client:id,name', 'contract.freelancer:id,name'])
            ->where('target_type', 'contract')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($report) => [
                'id' => $report->id,
                'title' => $report->description,
                'status' => $report->status,
                'reporter_name' => $report->reporter?->name,
                'client_name' => $report->contract?->client?->name,
                'freelancer_name' => $report->contract?->freelancer?->name,
                'amount' => $report->contract?->amount,
                'created_at' => $report->created_at,
            ]);
    }

    private function monthlyUsersGrowth(): array
    {
        return collect(range(5, 0))
            ->map(function ($monthsBack) {
                $date = now()->subMonths($monthsBack);

                return [
                    'month' => $date->format('F'),
                    'count' => User::where('role', '!=', 'admin')
                        ->whereDate('created_at', '<=', $date->copy()->endOfMonth())
                        ->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function monthlyRevenue(): array
    {
        $adminWallet = Wallet::where('type', 'admin')->first();

        if (! $adminWallet) {
            return [];
        }

        $from = now()->subMonths(5)->startOfMonth();

        $rows = $adminWallet->transactions()
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->whereIn('type', ['admin_receive', 'commission', 'platform_commission'])
            ->where('direction', 'credit')
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get()
            ->keyBy(fn ($row) => $row->year . '-' . $row->month);

        return collect(range(5, 0))
            ->map(function ($monthsBack) use ($rows) {
                $date = now()->subMonths($monthsBack);
                $key = $date->year . '-' . $date->month;

                return [
                    'month' => $date->format('F'),
                    'total' => (float) ($rows[$key]?->total ?? 0),
                ];
            })
            ->values()
            ->all();
    }
}
