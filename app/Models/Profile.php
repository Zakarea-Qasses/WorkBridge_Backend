<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
    'user_id',
    'job_title',
    'phone',
    'address',
    'description',
    'bio',
     ];

    public function user()
    {
     return $this->belongsTo(User::class);
    }

     public function skills()
    {
     return $this->belongsToMany(Skill::class,'profile_skill');
    }
}
