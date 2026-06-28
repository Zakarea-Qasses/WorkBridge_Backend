<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Report;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserProject;
use App\Services\ContractService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'target_type' => ['nullable', 'in:user,project,service,contract,general'],
            'target_id' => ['nullable', 'integer'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:support,complaint,dispute,payment,technical'],
            'priority' => ['nullable', 'in:low,normal,high'],
            'description' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:255'],
        ]);

        $reporter = $request->user();
        $data['target_type'] = $data['target_type'] ?? 'general';
        $data['target_id'] = $data['target_id'] ?? $reporter->id;

        if ($data['target_type'] === 'user') {
            $target = User::findOrFail($data['target_id']);

            if ($target->id === $reporter->id) {
                return response()->json([
                    'message' => 'لا يمكنك الإبلاغ عن نفسك.',
                ], 422);
            }

            if ($target->role === 'admin') {
                return response()->json([
                    'message' => 'لا يمكن الإبلاغ عن الأدمن.',
                ], 403);
            }
        }

        if ($data['target_type'] === 'project') {
            $target = UserProject::findOrFail($data['target_id']);

            if ($target->user_id === $reporter->id) {
                return response()->json([
                    'message' => 'لا يمكنك الإبلاغ عن مشروعك.',
                ], 422);
            }
        }

        if ($data['target_type'] === 'service') {
            $target = Service::findOrFail($data['target_id']);

            if ($target->user_id === $reporter->id) {
                return response()->json([
                    'message' => 'لا يمكنك الإبلاغ عن خدمتك.',
                ], 422);
            }
        }

        if ($data['target_type'] === 'contract') {
            $contract = Contract::findOrFail($data['target_id']);

            if (! in_array($reporter->id, [$contract->client_id, $contract->freelancer_id], true)) {
                return response()->json([
                    'message' => 'لا يمكنك فتح نزاع على عقد لا يخصك.',
                ], 403);
            }

            $data['contract_id'] = $contract->id;
        }

        if (isset($data['contract_id']) && $data['target_type'] !== 'contract') {
            $contract = Contract::findOrFail($data['contract_id']);

            if (! in_array($reporter->id, [$contract->client_id, $contract->freelancer_id], true)) {
                return response()->json([
                    'message' => 'لا يمكنك ربط البلاغ بعقد لا يخصك.',
                ], 403);
            }
        }

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'contract_id' => $data['contract_id'] ?? null,
            'title' => $data['title'] ?? null,
            'category' => $data['category'] ?? 'support',
            'priority' => $data['priority'] ?? 'normal',
            'description' => $data['description'],
            'attachments' => $data['attachments'] ?? null,
            'status' => 'pending',
        ]);

        if ($report->contract_id) {
            $this->contractService->openDispute($report->contract);
        }

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'new_report',
                'title' => 'بلاغ جديد',
                'message' => $reporter->name . ' أرسل بلاغا جديدا على ' . $data['target_type'],
            ]);
        }

        return response()->json([
            'message' => 'تم إرسال البلاغ بنجاح.',
            'report' => $this->appendAdminSummaries($report->fresh(['reporter'])),
        ], 201);
    }

    public function myReports(Request $request)
    {
        $reports = Report::where('reporter_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'reports' => $reports,
        ]);
    }

    public function latestMine(Request $request)
    {
        $report = Report::where('reporter_id', $request->user()->id)
            ->latest()
            ->first();

        return response()->json([
            'report' => $report ? $this->appendAdminSummaries($report->load('reporter')) : null,
        ]);
    }

    public function index()
    {
        $reports = Report::with('reporter')
            ->latest()
            ->get();

        $reports->each(fn (Report $report) => $this->appendAdminSummaries($report));

        return response()->json([
            'reports' => $reports,
        ]);
    }

    public function adminDecision(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
            'admin_decision' => ['nullable', 'string'],
            'admin_action' => ['nullable', 'in:refund_client,release_freelancer'],
        ]);

        $report = Report::findOrFail($id);

        $report->update([
            'status' => $data['status'],
            'admin_decision' => $data['admin_decision'] ?? null,
        ]);

        if ($report->contract && $data['status'] === 'accepted' && isset($data['admin_action'])) {
            if ($data['admin_action'] === 'refund_client') {
                $this->contractService->refundClient($report->contract);
            }

            if ($data['admin_action'] === 'release_freelancer') {
                $this->contractService->releaseFreelancerFromDispute($report->contract);
            }
        }

        UserNotification::create([
            'user_id' => $report->reporter_id,
            'type' => 'report_decision',
            'title' => $data['status'] === 'accepted'
                ? 'تم قبول البلاغ'
                : 'تم رفض البلاغ',
            'message' => $data['status'] === 'accepted'
                ? 'تم قبول البلاغ الذي أرسلته.'
                : 'تم رفض البلاغ الذي أرسلته.',
        ]);

        return response()->json([
            'message' => 'تم تحديث قرار الأدمن.',
            'report' => $this->appendAdminSummaries($report->fresh(['reporter'])),
        ]);
    }

    private function appendAdminSummaries(Report $report): Report
    {
        $report->setAttribute('target_summary', $this->targetSummary($report));
        $report->setAttribute('contract_summary', $this->contractSummary($report));

        return $report;
    }

    private function targetSummary(Report $report): ?array
    {
        if (! $report->target_id) {
            return null;
        }

        if ($report->target_type === 'user') {
            $user = User::find($report->target_id);

            return $user ? [
                'id' => $user->id,
                'type' => 'user',
                'title' => $user->name,
                'owner_name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ] : null;
        }

        if ($report->target_type === 'project') {
            $project = UserProject::with('user:id,name,email')->find($report->target_id);

            return $project ? [
                'id' => $project->id,
                'type' => 'project',
                'title' => $project->title,
                'owner_name' => $project->user?->name,
                'email' => $project->user?->email,
                'status' => $project->status,
                'amount' => $project->budget,
            ] : null;
        }

        if ($report->target_type === 'service') {
            $service = Service::with('user:id,name,email')->find($report->target_id);

            return $service ? [
                'id' => $service->id,
                'type' => 'service',
                'title' => $service->title,
                'owner_name' => $service->user?->name,
                'email' => $service->user?->email,
                'status' => $service->status,
                'amount' => $service->price,
            ] : null;
        }

        if ($report->target_type === 'contract') {
            $contract = Contract::with(['client:id,name,email', 'freelancer:id,name,email'])->find($report->target_id);

            return $contract ? [
                'id' => $contract->id,
                'type' => 'contract',
                'title' => 'Contract #' . $contract->id,
                'owner_name' => $contract->client?->name,
                'email' => $contract->client?->email,
                'status' => $contract->status,
                'amount' => $contract->amount,
            ] : null;
        }

        return [
            'id' => $report->target_id,
            'type' => $report->target_type,
            'title' => $report->title,
        ];
    }

    private function contractSummary(Report $report): ?array
    {
        if (! $report->contract_id) {
            return null;
        }

        $contract = Contract::with([
            'client:id,name,email',
            'freelancer:id,name,email',
            'project:id,title',
            'serviceRequest:id,title',
            'jobPost:id,title',
        ])->find($report->contract_id);

        if (! $contract) {
            return null;
        }

        return [
            'id' => $contract->id,
            'amount' => $contract->amount,
            'commission_amount' => $contract->commission_amount,
            'freelancer_amount' => $contract->freelancer_amount,
            'status' => $contract->status,
            'client_name' => $contract->client?->name,
            'client_email' => $contract->client?->email,
            'freelancer_name' => $contract->freelancer?->name,
            'freelancer_email' => $contract->freelancer?->email,
            'subject_title' => $contract->project?->title
                ?? $contract->serviceRequest?->title
                ?? $contract->jobPost?->title,
        ];
    }
}
