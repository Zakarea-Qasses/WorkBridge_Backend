<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\UserNotification;
use App\Models\UserProject;
use App\Services\ContractService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    public function store(Request $request, $projectId)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'فقط المستخدم الشخصي يمكنه التقديم على المشاريع.',
            ], 403);
        }

        $project = UserProject::findOrFail($projectId);

        if ($project->user_id === $user->id) {
            return response()->json([
                'message' => 'لا يمكنك التقديم على مشروعك.',
            ], 403);
        }

        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:1'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string'],
        ]);

        $exists = Application::where('user_project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'لقد قدمت على هذا المشروع مسبقا.',
            ], 409);
        }

        $application = Application::create([
            'user_project_id' => $project->id,
            'user_id' => $user->id,
            'price' => $data['price'],
            'duration_days' => $data['duration_days'],
            'description' => $data['description'],
            'status' => 'pending',
        ]);

        UserNotification::create([
            'user_id' => $project->user_id,
            'type' => 'project_application',
            'title' => 'وصل عرض جديد على مشروعك',
            'message' => $user->name . ' قدم عرضا على مشروع: ' . $project->title,
        ]);

        return response()->json([
            'message' => 'تم إرسال العرض بنجاح.',
            'application' => $application,
        ], 201);
    }

    public function received(Request $request)
    {
        $user = $request->user();

        $applications = Application::with([
            'user:id,name,email',
            'project:id,user_id,title',
        ])
            ->whereHas('project', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'applications' => $applications,
        ]);
    }

    public function myApplications(Request $request)
    {
        $applications = Application::with([
            'project:id,title,user_id',
        ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'applications' => $applications,
        ]);
    }

    public function accept(Request $request, $id)
    {
        $application = Application::with('project')->findOrFail($id);

        if ($application->project->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'لا يمكنك قبول عرض على مشروع لا تملكه.',
            ], 403);
        }

        $application->update([
            'status' => 'accepted',
        ]);

        Application::where('user_project_id', $application->user_project_id)
            ->where('id', '!=', $application->id)
            ->update(['status' => 'rejected']);

        $contract = $this->contractService->createFromApplication($application);

        UserNotification::create([
            'user_id' => $application->user_id,
            'type' => 'application_accepted',
            'title' => 'تم قبول عرضك',
            'message' => 'تم قبول عرضك على مشروع: ' . $application->project->title,
        ]);

        return response()->json([
            'message' => 'تم قبول العرض بنجاح.',
            'application' => $application,
            'contract' => $contract,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $application = Application::with('project')->findOrFail($id);

        if ($application->project->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'لا يمكنك رفض عرض على مشروع لا تملكه.',
            ], 403);
        }

        $application->update([
            'status' => 'rejected',
        ]);

        UserNotification::create([
            'user_id' => $application->user_id,
            'type' => 'application_rejected',
            'title' => 'تم رفض عرضك',
            'message' => 'تم رفض عرضك على مشروع: ' . $application->project->title,
        ]);

        return response()->json([
            'message' => 'تم رفض العرض.',
            'application' => $application,
        ]);
    }
}
