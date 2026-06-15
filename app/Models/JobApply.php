<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApply extends Model
{
    protected $table = 'job_apply';

    protected $fillable = [
        'job_id',
        'user_id',
        'status',
    ];

    public function job()
    {
        return $this->belongsTo(JobPost::class, 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
