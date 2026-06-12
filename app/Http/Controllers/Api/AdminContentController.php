<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\JobPost;
use App\Models\Service;
use App\Models\UserProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminContentController extends Controller
{
    public function projects(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        $projects = UserProject::with(['user:id,name,email', 'category:id,name', 'governorate:id,name', 'city:id,name,governorate_id', 'skills:id,name'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($data['governorate_id'] ?? null, fn ($query, $governorateId) => $query->where('governorate_id', $governorateId))
            ->when($data['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->latest()
            ->paginate(10);

        return response()->json(['projects' => $projects]);
    }

    public function services(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $services = Service::with(['user:id,name,email', 'category:id,name'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(10);

        return response()->json(['services' => $services]);
    }

    public function jobs(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        $jobs = JobPost::with(['company.user:id,name,email', 'city.governorate'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->when($data['governorate_id'] ?? null, function ($query, $governorateId) {
                $query->whereHas('city', fn ($cityQuery) => $cityQuery->where('governorate_id', $governorateId));
            })
            ->latest()
            ->paginate(10);

        return response()->json(['jobs' => $jobs]);
    }

    public function updateProjectStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $project = UserProject::findOrFail($id);
        $project->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'تم تحديث حالة المشروع بنجاح.',
            'project' => $project->load(['user:id,name,email', 'category:id,name', 'governorate:id,name', 'city:id,name,governorate_id', 'skills:id,name']),
        ]);
    }

    public function updateServiceStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $service = Service::findOrFail($id);
        $service->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'تم تحديث حالة الخدمة بنجاح.',
            'service' => $service->load(['user:id,name,email', 'category:id,name']),
        ]);
    }

    public function updateJobStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $job = JobPost::findOrFail($id);
        $job->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'تم تحديث حالة الوظيفة بنجاح.',
            'job' => $job->load(['company.user:id,name,email', 'city.governorate']),
        ]);
    }

    public function destroyProject(int $id)
    {
        UserProject::findOrFail($id)->delete();

        return response()->json(['message' => 'تم حذف المشروع بنجاح.']);
    }

    public function destroyService(int $id)
    {
        Service::findOrFail($id)->delete();

        return response()->json(['message' => 'تم حذف الخدمة بنجاح.']);
    }

    public function destroyJob(int $id)
    {
        JobPost::findOrFail($id)->delete();

        return response()->json(['message' => 'تم حذف الوظيفة بنجاح.']);
    }

    public function categories()
    {
        return response()->json([
            'categories' => Category::latest()->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create($data);

        return response()->json([
            'message' => 'تم إنشاء التصنيف بنجاح.',
            'category' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'تم تحديث التصنيف بنجاح.',
            'category' => $category,
        ]);
    }

    public function destroyCategory(int $id)
    {
        $category = Category::findOrFail($id);

        if ($category->services()->exists() || UserProject::where('category_id', $category->id)->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف التصنيف لأنه مرتبط بمحتوى موجود.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'تم حذف التصنيف بنجاح.']);
    }
}
