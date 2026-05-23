<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
public function run(): void
{
    $categories = [
        'Programming',
        'Web Development',
        'Mobile Development',
        'Artificial Intelligence',
        'Machine Learning',
        'Cyber Security',
        'Networking',
        'Cloud Computing',
        'DevOps',
        'Databases',
        'Software Engineering',
        'Algorithms & Data Structures',
        'Operating Systems',
        'UI/UX Design',
        'API Development',
        'Testing & QA',
        'Linux & Servers',
        'Career & Interview',
        'Tech News',
    ];

    foreach($categories as $category){
        Category::firstOrCreate([
            'name'=>$category
        ]);
    }

}
}
