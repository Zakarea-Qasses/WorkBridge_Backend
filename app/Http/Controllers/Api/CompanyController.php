<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Skill;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function show(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'message' => 'لم يتم العثور على ملف الشركة',
            ], 404);
        }

        return response()->json([
            'company' => $company->load(['skills', 'governorate', 'city']),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            return response()->json([
                'message' => 'لم يتم العثور على ملف الشركة',
            ], 404);
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
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

        $user->update([
            'name' => $data['company_name'],
        ]);

        $company->update([
            'company_name' => $data['company_name'],
            'description' => $data['description'] ?? $company->description,
            'website' => $data['website'] ?? $company->website,
            'location' => $data['location'] ?? $company->location,
            'governorate_id' => $data['governorate_id'] ?? $company->governorate_id,
            'city_id' => $data['city_id'] ?? $company->city_id,
            'phone' => $data['phone'] ?? $company->phone,
        ]);

        if (array_key_exists('skills', $data)) {
            $skillIds = [];

            foreach ($data['skills'] as $skillName) {
                $skillName = trim($skillName);

                if ($skillName === '') {
                    continue;
                }

                $skill = Skill::firstOrCreate(['name' => $skillName]);
                $skillIds[] = $skill->id;
            }

            $company->skills()->sync($skillIds);
        }

        return response()->json([
            'message' => 'تم تحديث بيانات الشركة بنجاح.',
            'company' => $company->fresh()->load(['skills', 'governorate', 'city']),
        ]);
    }
}
