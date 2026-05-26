<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

class Governorate extends Model
{
    protected $fillable = ['name'];
    public function cities()
    {
        $this->hasMany(City::class);
        return;
    }
}
