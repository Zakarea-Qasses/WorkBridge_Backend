<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
    'user_id',
    'company_name',
    'logo',
    'website',
    'location',
    'description',
    'is_verified',
    ];

   public function user()
   {
    return $this->belongsTo(User::class);
   }

    public function skills()
    {
     return $this->belongsToMany(Skill::class,'company_skill');
    }
}
