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
        ]);

        $projects = UserProject::with(['user:id,name,email', 'category:id,name', 'skills:id,name'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
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
        ]);

        $jobs = JobPost::with(['company.user:id,name,email', 'city'])
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
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
            'message' => 'Project status updated successfully.',
            'project' => $project->load(['user:id,name,email', 'category:id,name', 'skills:id,name']),
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
            'message' => 'Service status updated successfully.',
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
            'message' => 'Job status updated successfully.',
            'job' => $job->load(['company.user:id,name,email', 'city']),
        ]);
    }

    public function destroyProject(int $id)
    {
        UserProject::findOrFail($id)->delete();

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    public function destroyService(int $id)
    {
        Service::findOrFail($id)->delete();

        return response()->json(['message' => 'Service deleted successfully.']);
    }

    public function destroyJob(int $id)
    {
        JobPost::findOrFail($id)->delete();

        return response()->json(['message' => 'Job deleted successfully.']);
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
            'message' => 'Category created successfully.',
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
            'message' => 'Category updated successfully.',
            'category' => $category,
        ]);
    }

    public function destroyCategory(int $id)
    {
        $category = Category::findOrFail($id);

        if ($category->services()->exists() || UserProject::where('category_id', $category->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete category while it has content.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
