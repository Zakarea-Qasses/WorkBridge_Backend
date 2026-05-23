<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\public\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        return response()->json([
            'categories'=>Category::all()
        ]);
    }
}
