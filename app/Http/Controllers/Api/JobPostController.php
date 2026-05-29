<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use Illuminate\Http\Request;

class JobPostController extends Controller
{
    public function index()
    {
        $jobs = JobPost::with(['company:id,company_name,logo', 'city'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function show($id)
    {
        $job = JobPost::with(['company:id,company_name,logo,description', 'city'])
            ->findOrFail($id);

        return response()->json([
            'job' => $job
        ]);
    }

    public function myJobs(Request $request)
    {
        $company = $request->user()->company;

        if (!$company) {
            return response()->json([
                'message' => 'Company profile not found'
            ], 404);
        }

        $jobs = JobPost::with('city')
            ->where('company_id', $company->id)
            ->latest()
            ->get();

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only company users can create job posts'
            ], 403);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'message' => 'Company profile not found'
            ], 404);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location_type' => ['nullable', 'in:remote,on_site,hybrid'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $job = JobPost::create([
            'company_id' => $company->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'location_type' => $data['location_type'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'salary' => $data['salary'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Job post created successfully',
            'job' => $job->load(['company', 'city'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'You cannot update a job post you do not own'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'location_type' => ['nullable', 'in:remote,on_site,hybrid'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,paused,closed'],
        ]);

        $job->update($data);

        return response()->json([
            'message' => 'Job post updated successfully',
            'job' => $job->load(['company', 'city'])
        ]);
    }

    public function pause(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'You cannot pause a job post you do not own'
            ], 403);
        }

        $job->update([
            'status' => 'paused'
        ]);

        return response()->json([
            'message' => 'Job post paused successfully',
            'job' => $job
        ]);
    }

    public function activate(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'You cannot activate a job post you do not own'
            ], 403);
        }

        $job->update([
            'status' => 'active'
        ]);

        return response()->json([
            'message' => 'Job post activated successfully',
            'job' => $job
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'You cannot delete a job post you do not own'
            ], 403);
        }

        $job->delete();

        return response()->json([
            'message' => 'Job post deleted successfully'
        ]);
    }
}