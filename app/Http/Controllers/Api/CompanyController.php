<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function show(Request $request)
    {
         $company=$request->user()->company;
        return response()->json([
            'company' => $company->load('skills')
        ]);
    }

    public function update(Request $request)
    {

     $user=$request->user();

     $company=$user->company;

    if(!$company){
     return response()->json([
    'meesage'=>'لم نجد ملف الشركة المطلوب'
     ],404);
    }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'skills'=>['nullable','array'],
            'skills.*'=>['string','max:255'],
        ]);

        $company = $request->user()->company;

         if(isset($data['company_name'])){

            $user->update([
                'name'=>$data['company_name'],
            ]);

         $company->update([
            'company_name'=>$data['company_name'] ?? $company->name,
            'description'=>$data['description'] ?? $company->description,
            'website'=>$data['website']??$company->website,
            'location'=>$data['location'] ?? $company->location,
            'phone'=>$data['phone']??$company->phone,
        ]);

        
        if(isset($data['skills'])){
        $skillIds=[];

        foreach($data['skills'] as $skillName){
            $skill = \App\Models\Skill::firstOrCreate([
                'name'=>trim($skillName)
            ]);
            
            $skillIds[] = $skill->id;
        } 
        $company->skills()->sync($skillIds);
        }

        return response()->json([
            'message' => 'تم تحديث بيانات الشركة بنجاح',
            'company' => $company->load('skills')
        ]);
    }
    }
}