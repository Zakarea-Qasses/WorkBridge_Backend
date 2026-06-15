<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserProjectController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
        ]);

        $projects = UserProject::with([
            'user:id,name,role',
            'category:id,name',
            'governorate:id,name',
            'city:id,name,governorate_id',
            'skills:id,name',
        ])
            ->where('status', $data['status'] ?? 'active')
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($data['governorate_id'] ?? null, fn ($query, $governorateId) => $query->where('governorate_id', $governorateId))
            ->when($data['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->when($data['min_price'] ?? null, fn ($query, $price) => $query->where('budget', '>=', $price))
            ->when($data['max_price'] ?? null, fn ($query, $price) => $query->where('budget', '<=', $price))
            ->when(($data['type'] ?? null) && Schema::hasColumn('user_projects', 'type'), fn ($query) => $query->where('type', $data['type']))
            ->latest()
            ->get();

        return response()->json([
            'projects' => $projects
        ]);
    }

    public function show($id)
    {
        $project = UserProject::with([
            'user:id,name,role',
            'category:id,name',
            'governorate:id,name',
            'city:id,name,governorate_id',
            'skills:id,name',
        ])->where('status', 'active')->findOrFail($id);

        return response()->json([
            'project' => $project
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'فقط المستخدم الشخصي يمكنه نشر مشروع'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'budget' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],

            'category_id' => ['required', 'exists:categories,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],

            'skills' => ['required', 'array'],
            'skills.*' => ['exists:skills,id'],
        ]);

        if (! empty($data['governorate_id']) && ! empty($data['city_id'])) {
            $cityBelongsToGovernorate = City::where('id', $data['city_id'])
                ->where('governorate_id', $data['governorate_id'])
                ->exists();

            if (! $cityBelongsToGovernorate) {
                return response()->json([
                    'message' => 'المدينة المختارة لا تتبع للمحافظة المختارة.',
                ], 422);
            }
        }

        $project = UserProject::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'governorate_id' => $data['governorate_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'budget' => $data['budget'],
            'duration_days' => $data['duration_days'],
            'status' => 'active',
        ]);

        $project->skills()->sync($data['skills']);

        return response()->json([
            'message' => 'تم نشر المشروع بنجاح',
            'project' => $project->load([
                'category',
                'governorate',
                'city',
                'skills',
            ])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $project = UserProject::findOrFail($id);
        $user = $request->user();

        if ($project->user_id !== $user->id) {
            return response()->json([
                'message' => 'لا يمكنك تعديل مشروع لا تملكه'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],

            'category_id' => ['sometimes', 'exists:categories,id'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],

            'skills' => ['sometimes', 'array'],
            'skills.*' => ['exists:skills,id'],
        ]);

        if (! empty($data['governorate_id']) && ! empty($data['city_id'])) {
            $cityBelongsToGovernorate = City::where('id', $data['city_id'])
                ->where('governorate_id', $data['governorate_id'])
                ->exists();

            if (! $cityBelongsToGovernorate) {
                return response()->json([
                    'message' => 'المدينة المختارة لا تتبع للمحافظة المختارة.',
                ], 422);
            }
        }

        $project->update($data);

        if (isset($data['skills'])) {
            $project->skills()->sync($data['skills']);
        }

        return response()->json([
            'message' => 'تم تعديل المشروع بنجاح',
            'project' => $project->load([
                'category',
                'governorate',
                'city',
                'skills',
            ])
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $project = UserProject::findOrFail($id);
        $user = $request->user();
        
        if ($project->user_id !== $user->id) {
            return response()->json([
                'message' => 'لا يمكنك حذف مشروع لا تملكه'
            ], 403);
        }

        $project->delete();

        return response()->json([
            'message' => 'تم حذف المشروع بنجاح'
        ]);
    }
}
