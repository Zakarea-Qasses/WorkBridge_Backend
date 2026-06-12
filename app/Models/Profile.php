<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
    'user_id',
    'governorate_id',
    'city_id',
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

    public function governorate()
    {
     return $this->belongsTo(Governorate::class);
    }

    public function city()
    {
     return $this->belongsTo(City::class);
    }
}
