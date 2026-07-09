<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApply;
use App\Models\JobPost;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobApplyController extends Controller
{
    public function store(Request $request, $jobId)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'Only personal users can apply to jobs.',
            ], 403);
        }

        $job = JobPost::with('company')->findOrFail($jobId);

        if ($job->status !== 'active') {
            return response()->json([
                'message' => 'You cannot apply to an inactive job.',
            ], 422);
        }

        $exists = JobApply::where('job_id', $job->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already applied to this job.',
            ], 409);
        }

        $application = JobApply::create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        if ($job->company) {
            UserNotification::create([
                'user_id' => $job->company->user_id,
                'type' => 'job_application',
                'title' => 'وصل طلب توظيف جديد',
                'message' => $user->name . ' تقدم على الوظيفة: ' . $job->title,
            ]);
        }

        return response()->json([
            'message' => 'Job application sent successfully.',
            'application' => $application->load(['job:id,title,company_id', 'user:id,name,email']),
        ], 201);
    }

    public function applications(Request $request, $jobId)
    {
        $job = JobPost::findOrFail($jobId);
        $company = $request->user()->company;

        if (! $company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'You cannot view applications for a job you do not own.',
            ], 403);
        }

        $applications = JobApply::with(['user:id,name,email', 'user.profile'])
            ->where('job_id', $job->id)
            ->latest()
            ->get();

        return response()->json([
            'applications' => $applications,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected'])],
        ]);

        $application = JobApply::with('job')->findOrFail($id);
        $company = $request->user()->company;

        if (! $company || $application->job->company_id !== $company->id) {
            return response()->json([
                'message' => 'You cannot update an application for a job you do not own.',
            ], 403);
        }

        $rejectedApplications = collect();

        DB::transaction(function () use ($application, $data, &$rejectedApplications) {
            if ($data['status'] === 'accepted') {
                $rejectedApplications = JobApply::with('user:id,name,email')
                    ->where('job_id', $application->job_id)
                    ->where('id', '!=', $application->id)
                    ->where('status', '!=', 'rejected')
                    ->get();

                JobApply::where('job_id', $application->job_id)
                    ->where('id', '!=', $application->id)
                    ->update(['status' => 'rejected']);
            }

            $application->update([
                'status' => $data['status'],
            ]);
        });

        $notificationType = $data['status'] === 'accepted' ? 'job_application_accepted' : 'job_application_' . $data['status'];
        $notificationTitle = match ($data['status']) {
            'accepted' => 'تم قبول طلبك الوظيفي',
            'rejected' => 'تم رفض طلبك الوظيفي',
            default => 'تم تحديث حالة طلبك الوظيفي',
        };

        UserNotification::create([
            'user_id' => $application->user_id,
            'type' => $notificationType,
            'title' => $notificationTitle,
            'message' => 'تم تحديث حالة طلبك على الوظيفة "' . $application->job->title . '" إلى: ' . $data['status'],
        ]);

        foreach ($rejectedApplications as $rejectedApplication) {
            UserNotification::create([
                'user_id' => $rejectedApplication->user_id,
                'type' => 'job_application_rejected',
                'title' => 'تم رفض طلبك الوظيفي',
                'message' => 'تم رفض طلبك على الوظيفة: ' . $application->job->title,
            ]);
        }

        return response()->json([
            'message' => 'Application status updated successfully.',
            'application' => $application->fresh()->load(['job:id,title,company_id', 'user:id,name,email']),
        ]);
    }

    public function myApplications(Request $request)
    {
        $applications = JobApply::with(['job.company:id,company_name,logo', 'job.city.governorate'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'applications' => $applications,
        ]);
    }
}
