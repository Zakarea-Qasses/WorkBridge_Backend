<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['reporter_id','reported_user_id','description','status','admin_decision'];

    public function reporter(){
        return $this->belongsTo(User::class,'reporter_id');
    }
}
