<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProject extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'governorate_id',
        'city_id',
        'title',
        'description',
        'budget',
        'duration_days',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

  /*  public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
*/
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'project_skill');
    }

    public function applications(){
        return $this->hasMany(Application::class);
    }
}

