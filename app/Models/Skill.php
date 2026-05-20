<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name'];

     public function profiles()
    {
     return $this->belongsToMany(Profile::class,'profile_skill');
    }

     public function compaines()
    {
     return $this->belongsToMany(Company::class,'company_skill');
    }

}
