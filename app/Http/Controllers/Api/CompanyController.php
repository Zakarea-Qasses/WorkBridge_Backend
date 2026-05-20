<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'company' => $request->user()->company
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $company = $request->user()->company;
        $company->update($data);

        return response()->json([
            'message' => 'تم تحديث بيانات الشركة بنجاح',
            'company' => $company
        ]);
    }
}