<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function store(Request $request, $serviceId)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'فقط المستخدم الشخصي يمكنه طلب خدمة'
            ], 403);
        }

        $service = Service::findOrFail($serviceId);

        if ($service->user_id === $user->id) {
            return response()->json([
                'message' => 'لا يمكنك طلب خدمتك أنت'
            ], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'references' => ['nullable', 'string'],
            'delivery_days' => ['required', 'integer', 'min:1'],
        ]);

        $serviceRequest = ServiceRequest::create([
            'service_id' => $service->id,
            'client_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'references' => $data['references'] ?? null,
            'delivery_days' => $data['delivery_days'],
            'status' => 'pending',
        ]);

        UserNotification::create([
            'user_id' => $service->user_id,
            'type' => 'service_request',
            'title' => 'وصل طلب جديد على خدمتك',
            'message' => $user->name . ' طلب خدمة: ' . $service->title,
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الخدمة بنجاح',
            'service_request' => $serviceRequest
        ], 201);
    }

    public function received(Request $request)
    {
        $requests = ServiceRequest::with([
            'client:id,name,email',
            'service:id,user_id,title,price,delivery_days'
        ])
            ->whereHas('service', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'requests' => $requests
        ]);
    }

    public function myRequests(Request $request)
    {
        $requests = ServiceRequest::with([
            'service:id,user_id,title,price,delivery_days',
            'service.user:id,name,email'
        ])
            ->where('client_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'requests' => $requests
        ]);
    }

    public function accept(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::with('service')->findOrFail($id);

        if ($serviceRequest->service->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'لا يمكنك قبول طلب على خدمة لا تملكها'
            ], 403);
        }

        $serviceRequest->update([
            'status' => 'accepted'
        ]);

        UserNotification::create([
            'user_id' => $serviceRequest->client_id,
            'type' => 'service_request_accepted',
            'title' => 'تم قبول طلب الخدمة',
            'message' => 'تم قبول طلبك على خدمة: ' . $serviceRequest->service->title,
        ]);

        return response()->json([
            'message' => 'تم قبول الطلب',
            'service_request' => $serviceRequest
        ]);
    }

    public function reject(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::with('service')->findOrFail($id);

        if ($serviceRequest->service->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'لا يمكنك رفض طلب على خدمة لا تملكها'
            ], 403);
        }

        $serviceRequest->update([
            'status' => 'rejected'
        ]);
        
        UserNotification::create([
            'user_id' => $serviceRequest->client_id,
            'type' => 'service_request_rejected',
            'title' => 'تم رفض طلب الخدمة',
            'message' => 'تم رفض طلبك على خدمة: ' . $serviceRequest->service->title,
        ]);

        return response()->json([
            'message' => 'تم رفض الطلب',
            'service_request' => $serviceRequest
        ]);
    }
}