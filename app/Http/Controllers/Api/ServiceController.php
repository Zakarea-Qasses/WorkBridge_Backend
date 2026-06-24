<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'paused', 'closed'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
        ]);

        $services = Service::with([
            'user:id,name,role',
            'category:id,name'
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
            ->when($data['min_price'] ?? null, fn ($query, $price) => $query->where('price', '>=', $price))
            ->when($data['max_price'] ?? null, fn ($query, $price) => $query->where('price', '<=', $price))
            ->when(($data['type'] ?? null) && Schema::hasColumn('services', 'type'), fn ($query) => $query->where('type', $data['type']))
            ->latest()
            ->get();

        return response()->json([
            'services' => $services
        ]);
    }

    public function show($id)
    {
        $service = Service::with([
            'user:id,name,role',
            'category:id,name'
        ])->where('status', 'active')->findOrFail($id);

        return response()->json([
            'service' => $service
        ]);
    }

    public function companyBrowse(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'فقط حسابات الشركات يمكنها تصفح صفحة خدمات الشركة'], 403);
        }

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $services = Service::with([
            'user:id,name,role',
            'category:id,name'
        ])
            ->where('status', 'active')
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->select('services.*')
            ->selectSub(
                Review::selectRaw('AVG(rating)')
                    ->whereColumn('reviewed_user_id', 'services.user_id'),
                'rating_avg'
            )
            ->withCount(['requests as orders_count'])
            ->latest()
            ->paginate(9);

        $services->getCollection()->transform(function ($service) {
            $service->rating_avg = round((float) $service->rating_avg, 2);

            return $service;
        });

        return response()->json([
            'available_services_count' => Service::where('status', 'active')->count(),
            'services' => $services,
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
            'price' => ['required', 'numeric', 'min:1'],
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
            'status' => 'active',
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
            'price' => ['sometimes', 'numeric', 'min:1'],
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
