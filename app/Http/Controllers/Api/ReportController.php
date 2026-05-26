<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Report;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserProject;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:user,project,service'],
            'target_id' => ['required', 'integer'],
            'description' => ['required', 'string'],
        ]);

        $reporter = $request->user();

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

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'description' => $data['description'],
            'status' => 'pending',
        ]);

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
        ]);

        $report = Report::findOrFail($id);

        $report->update([
            'status' => $data['status'],
            'admin_decision' => $data['admin_decision'] ?? null,
        ]);

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