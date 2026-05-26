<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'user_project_id',
        'user_id',
        'price',
        'duration_days',
        'description',
        'status',
    ];

    public function userproject()
    {
        return $this->belongsTo(UserProject::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}