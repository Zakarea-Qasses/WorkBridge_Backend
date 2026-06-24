<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobApply;
use App\Models\JobPost;
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

        $job = JobPost::findOrFail($jobId);

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

        DB::transaction(function () use ($application, $data) {
            if ($data['status'] === 'accepted') {
                JobApply::where('job_id', $application->job_id)
                    ->where('id', '!=', $application->id)
                    ->update(['status' => 'rejected']);
            }

            $application->update([
                'status' => $data['status'],
            ]);
        });

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
