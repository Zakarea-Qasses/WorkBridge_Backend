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
                    'message' => 'لا يمكنك الإبلاغ عن نفسك'
                ], 422);
            }

            if ($target->role === 'admin') {
                return response()->json([
                    'message' => 'لا يمكن الإبلاغ عن الأدمن'
                ], 403);
            }
        }

        if ($data['target_type'] === 'project') {
            $target = UserProject::findOrFail($data['target_id']);

            if ($target->user_id === $reporter->id) {
                return response()->json([
                    'message' => 'لا يمكنك الإبلاغ عن مشروعك'
                ], 422);
            }
        }

        if ($data['target_type'] === 'service') {
            $target = Service::findOrFail($data['target_id']);

            if ($target->user_id === $reporter->id) {
                return response()->json([
                    'message' => 'لا يمكنك الإبلاغ عن خدمتك'
                ], 422);
            }
        }

        if ($data['target_type'] === 'contract') {
            $contract = Contract::findOrFail($data['target_id']);

            if (! in_array($reporter->id, [$contract->client_id, $contract->freelancer_id], true)) {
                return response()->json([
                    'message' => 'لا يمكنك فتح نزاع على عقد لا يخصك'
                ], 403);
            }

            $data['contract_id'] = $contract->id;
        }

        if (isset($data['contract_id']) && $data['target_type'] !== 'contract') {
            $contract = Contract::findOrFail($data['contract_id']);

            if (! in_array($reporter->id, [$contract->client_id, $contract->freelancer_id], true)) {
                return response()->json([
                    'message' => 'لا يمكنك ربط البلاغ بعقد لا يخصك'
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
                'message' => $reporter->name . ' أرسل بلاغاً جديداً على ' . $data['target_type'],
            ]);
        }

        return response()->json([
            'message' => 'تم إرسال البلاغ بنجاح',
            'report' => $report
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
            'report' => $report,
        ]);
    }

    public function index()
    {
        $reports = Report::with('reporter')
            ->latest()
            ->get();

        return response()->json([
            'reports' => $reports
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
                ? 'تم قبول البلاغ الذي أرسلته'
                : 'تم رفض البلاغ الذي أرسلته',
        ]);

        return response()->json([
            'message' => 'تم تحديث قرار الأدمن',
            'report' => $report
        ]);
    }
}
