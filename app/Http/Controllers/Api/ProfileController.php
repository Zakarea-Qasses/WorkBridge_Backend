<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile=$request->user()->profile;

        return response()->json([
            'profile' => $profile->load('skills')
        ]);
    }

    public function update(Request $request)
    {

    $user=$request->user();
    $profile=$user->profile;

    if(!$profile){
     return response()->json([
    'meesage'=>'لم نجد الملف الشخصي المطلوب'
     ],404);
    }

        $data = $request->validate([
            'name'=>['string','max:100'],
            'job_title'=>['nullable','string','max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'description'=> ['nullable','string'],
            'bio' => ['nullable', 'string'],
            'skills'=>['nullable','array'],
            'skills.*'=>['string','max:255'],
        ]);

        if(isset($data['name'])){
            $user->update([
                'name'=>$data['name'],
            ]);

        $profile->update([
            'name'=>$data['name'] ?? $profile->name,
            'description'=>$data['description'] ?? $profile->description,
            'bio'=>$data['bio']??$profile->bio,
            'address'=>$data['addess'] ?? $profile->address,
            'phone'=>$data['phone']??$profile->phone,
        ]);

        if(isset($data['skills'])){
        $skillIds=[];

        foreach($data['skills'] as $skillName){
            $skill = Skill::firstOrCreate([
                'name'=>trim($skillName)
            ]);
            
            $skillIds[] = $skill->id;
        } 
        $profile->skills()->sync($skillIds);
        }

        return response()->json([
            'message' => 'تم تحديث البروفايل بنجاح',
            'profile' => $profile->load('skills')
        ]);
    }

}
}