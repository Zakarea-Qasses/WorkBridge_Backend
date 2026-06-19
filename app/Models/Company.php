<?php

namespace App\Models;

use App\Models\JobPost;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'governorate_id',
        'city_id',
        'company_name',
        'logo',
        'website',
        'location',
        'phone',
        'description',
        'is_verified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'company_skill');
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class);
    }

    public function documentRequests()
    {
        return $this->hasMany(CompanyDocumentRequest::class);
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
