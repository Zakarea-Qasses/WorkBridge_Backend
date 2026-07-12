<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;

class SkillController extends Controller
{
    public function index()
    {
        return response()->json([
            'skills' => Skill::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
