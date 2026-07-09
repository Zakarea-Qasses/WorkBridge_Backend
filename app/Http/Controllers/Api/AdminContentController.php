<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\JobPost;
use App\Models\Service;
use App\Models\UserNotification;
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

        UserNotification::create([
            'user_id' => $project->user_id,
            'type' => 'project_status_updated',
            'title' => 'تم تحديث حالة مشروعك',
            'message' => 'تم تغيير حالة مشروعك "' . $project->title . '" إلى: ' . $data['status'],
        ]);

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

        UserNotification::create([
            'user_id' => $service->user_id,
            'type' => 'service_status_updated',
            'title' => 'تم تحديث حالة خدمتك',
            'message' => 'تم تغيير حالة خدمتك "' . $service->title . '" إلى: ' . $data['status'],
        ]);

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

        $job = JobPost::with('company')->findOrFail($id);
        $job->update(['status' => $data['status']]);

        if ($job->company) {
            UserNotification::create([
                'user_id' => $job->company->user_id,
                'type' => 'job_status_updated',
                'title' => 'تم تحديث حالة الوظيفة',
                'message' => 'تم تغيير حالة الوظيفة "' . $job->title . '" إلى: ' . $data['status'],
            ]);
        }

        return response()->json([
            'message' => 'تم تحديث حالة الوظيفة بنجاح.',
            'job' => $job->load(['company.user:id,name,email', 'city.governorate']),
        ]);
    }

    public function destroyProject(int $id)
    {
        $project = UserProject::findOrFail($id);

        UserNotification::create([
            'user_id' => $project->user_id,
            'type' => 'project_deleted_by_admin',
            'title' => 'تم حذف مشروعك',
            'message' => 'تم حذف مشروعك من قبل الإدارة: ' . $project->title,
        ]);

        $project->delete();

        return response()->json(['message' => 'تم حذف المشروع بنجاح.']);
    }

    public function destroyService(int $id)
    {
        $service = Service::findOrFail($id);

        UserNotification::create([
            'user_id' => $service->user_id,
            'type' => 'service_deleted_by_admin',
            'title' => 'تم حذف خدمتك',
            'message' => 'تم حذف خدمتك من قبل الإدارة: ' . $service->title,
        ]);

        $service->delete();

        return response()->json(['message' => 'تم حذف الخدمة بنجاح.']);
    }

    public function destroyJob(int $id)
    {
        $job = JobPost::with('company')->findOrFail($id);

        if ($job->company) {
            UserNotification::create([
                'user_id' => $job->company->user_id,
                'type' => 'job_deleted_by_admin',
                'title' => 'تم حذف الوظيفة',
                'message' => 'تم حذف الوظيفة من قبل الإدارة: ' . $job->title,
            ]);
        }

        $job->delete();

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
