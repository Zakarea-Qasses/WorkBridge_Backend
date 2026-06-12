<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Review;
use App\Models\Skill;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'غير مصرح لك بعرض هذا الملف الشخصي'
            ], 403);
        }

        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'لم نجد الملف الشخصي المطلوب'
            ], 404);
        }

        $reviews = Review::where('reviewed_user_id', $user->id);

        return response()->json([
            'profile' => $profile->load(['skills', 'governorate', 'city']),
            'rating_avg' => round((float) $reviews->avg('rating'), 2),
            'reviews_count' => $reviews->count(),
        ], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'personal') {
            return response()->json([
                'message' => 'غير مصرح لك بتعديل هذا الملف الشخصي'
            ], 403);
        }

        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'لم نجد الملف الشخصي المطلوب'
            ], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:255'],
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

        if (isset($data['name'])) {
            $user->update([
                'name' => $data['name'],
            ]);
        }

        $profile->update([
            'name' => $data['name'] ?? $profile->name,
            'governorate_id' => $data['governorate_id'] ?? $profile->governorate_id,
            'city_id' => $data['city_id'] ?? $profile->city_id,
            'job_title' => $data['job_title'] ?? $profile->job_title,
            'description' => $data['description'] ?? $profile->description,
            'bio' => $data['bio'] ?? $profile->bio,
            'address' => $data['address'] ?? $profile->address,
            'phone' => $data['phone'] ?? $profile->phone,
        ]);

        if (array_key_exists('skills', $data)) {
            $skillIds = [];

            foreach ($data['skills'] as $skillName) {
                $skillName = trim($skillName);

                if ($skillName === '') {
                    continue;
                }

                $skill = Skill::firstOrCreate([
                    'name' => $skillName
                ]);

                $skillIds[] = $skill->id;
            }

            $profile->skills()->sync($skillIds);
        }

        return response()->json([
            'message' => 'تم تحديث البروفايل بنجاح',
            'profile' => $profile->fresh()->load(['skills', 'governorate', 'city'])
        ], 200);
    }
}
