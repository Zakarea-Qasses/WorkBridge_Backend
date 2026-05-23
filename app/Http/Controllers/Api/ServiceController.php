<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with([
            'user:id,name,role',
            'category:id,name'
        ])->latest()->get();

        return response()->json([
            'services' => $services
        ]);
    }

    public function show($id)
    {
        $service = Service::with([
            'user:id,name,role',
            'category:id,name'
        ])->findOrFail($id);

        return response()->json([
            'service' => $service
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'فقط المستخدم الشخصي يمكنه نشر خدمة'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_days' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $service = Service::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'price' => $data['price'],
            'delivery_days' => $data['delivery_days'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'تم نشر الخدمة بنجاح',
            'service' => $service
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $user = $request->user();

        if ($service->user_id !== $user->id) {
            return response()->json([
                'message' => 'لا يمكنك تعديل خدمة لا تملكها'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'delivery_days' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $service->update($data);

        return response()->json([
            'message' => 'تم تعديل الخدمة بنجاح',
            'service' => $service
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $user = $request->user();

        if ($service->user_id !== $user->id) {
            return response()->json([
                'message' => 'لا يمكنك حذف خدمة لا تملكها'
            ], 403);
        }

        $service->delete();

        return response()->json([
            'message' => 'تم حذف الخدمة بنجاح'
        ]);
    }
}