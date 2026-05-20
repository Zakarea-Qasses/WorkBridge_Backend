<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use Illuminate\Http\Request;

class JobPostController extends Controller
{
    public function index()
    {
        return JobPost::with('company')->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_type' => 'required|in:remote,onsite,hybrid',
            'city_id' => 'nullable|exists:cities,id',
            'salary' => 'nullable|numeric'
        ]);

        $job = JobPost::create($validated);

        return response()->json([
            'message' => 'تم انشاء الاعلان بنجاح',
            'data' => $job
        ]);
    }

    public function show(JobPost $jobPost)
    {
        return $jobPost->load('company');
    }

    public function update(Request $request, JobPost $jobPost)
    {
        $jobPost->update($request->all());

        return response()->json([
            'message' => 'تم تعديل الاعلان بنجاح',
            'data' => $jobPost
        ]);
    }

    public function destroy(JobPost $jobPost)
    {
        $jobPost->delete();

        return response()->json([
            'message' => 'تم حذف الاعلان بنجاح'
        ]);
    }
}