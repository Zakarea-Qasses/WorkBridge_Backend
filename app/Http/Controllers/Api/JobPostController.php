<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobPostController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
            'city_id' => ['nullable', 'exists:cities,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $jobs = JobPost::with(['company:id,company_name,logo', 'city.governorate'])
            ->where('status', $data['status'] ?? 'active')
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('company_name', 'like', "%{$search}%"));
                });
            })
            ->when($data['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->when($data['governorate_id'] ?? null, function ($query, $governorateId) {
                $query->whereHas('city', fn ($cityQuery) => $cityQuery->where('governorate_id', $governorateId));
            })
            ->when($data['min_salary'] ?? null, fn ($query, $salary) => $query->where('salary', '>=', $salary))
            ->when($data['max_salary'] ?? null, fn ($query, $salary) => $query->where('salary', '<=', $salary))
            ->latest()
            ->paginate(15);

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function show($id)
    {
        $job = JobPost::with(['company:id,company_name,logo,description', 'city.governorate'])
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
                'message' => 'لم يتم العثور على ملف الشركة'
            ], 404);
        }

        $jobs = JobPost::with('city.governorate')
            ->where('company_id', $company->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'jobs' => $jobs
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'فقط حسابات الشركات يمكنها إنشاء وظائف'
            ], 403);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'message' => 'لم يتم العثور على ملف الشركة'
            ], 404);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location_type' => ['nullable', 'in:remote,on_site,hybrid'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'salary' => ['nullable', 'numeric', 'min:1'],
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
            'message' => 'تم إنشاء الوظيفة بنجاح',
            'job' => $job->load(['company', 'city'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'لا يمكنك تعديل وظيفة لا تملكها'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'location_type' => ['nullable', 'in:remote,on_site,hybrid'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'salary' => ['nullable', 'numeric', 'min:1'],
            'status' => ['sometimes', 'in:active,paused,closed'],
        ]);

        $job->update($data);

        return response()->json([
            'message' => 'تم تحديث الوظيفة بنجاح',
            'job' => $job->load(['company', 'city'])
        ]);
    }

    public function pause(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'لا يمكنك إيقاف وظيفة لا تملكها'
            ], 403);
        }

        $job->update([
            'status' => 'paused'
        ]);

        return response()->json([
            'message' => 'تم إيقاف الوظيفة بنجاح',
            'job' => $job
        ]);
    }

    public function activate(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'لا يمكنك تفعيل وظيفة لا تملكها'
            ], 403);
        }

        $job->update([
            'status' => 'active'
        ]);

        return response()->json([
            'message' => 'تم تفعيل الوظيفة بنجاح',
            'job' => $job
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $job = JobPost::findOrFail($id);
        $company = $request->user()->company;

        if (!$company || $job->company_id !== $company->id) {
            return response()->json([
                'message' => 'لا يمكنك حذف وظيفة لا تملكها'
            ], 403);
        }

        $job->delete();

        return response()->json([
            'message' => 'تم حذف الوظيفة بنجاح'
        ]);
    }
}
