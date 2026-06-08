<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UserProject;
use Illuminate\Http\Request;

class UserProjectController extends Controller
{
    public function index()
    {
        $projects = UserProject::with([
            'user:id,name,role',
            'category:id,name',
/*            'governorate:id,name',
            'city:id,name,governorate_id',*/
            'skills:id,name',
        ])->where('status', 'active')->latest()->get();

        return response()->json([
            'projects' => $projects
        ]);
    }

    public function show($id)
    {
        $project = UserProject::with([
            'user:id,name,role',
            'category:id,name',
           /* 'governorate:id,name',
            'city:id,name,governorate_id',*/
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
           /* 'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => ['required', 'exists:cities,id'],*/

            'skills' => ['required', 'array'],
            'skills.*' => ['exists:skills,id'],
        ]);

        $project = UserProject::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
          /*  'governorate_id' => $data['governorate_id'],
            'city_id' => $data['city_id'],*/
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
              /*  'governorate',
                'city',*/
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
         /*   'governorate_id' => ['sometimes', 'exists:governorates,id'],
            'city_id' => ['sometimes', 'exists:cities,id'],*/

            'skills' => ['sometimes', 'array'],
            'skills.*' => ['exists:skills,id'],
        ]);

        $project->update($data);

        if (isset($data['skills'])) {
            $project->skills()->sync($data['skills']);
        }

        return response()->json([
            'message' => 'تم تعديل المشروع بنجاح',
            'project' => $project->load([
                'category',
               /* 'governorate',
                'city',*/
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
