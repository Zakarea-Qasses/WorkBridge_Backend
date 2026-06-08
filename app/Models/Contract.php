<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'client_id',
        'freelancer_id',
        'user_project_id',
        'service_request_id',
        'job_post_id',
        'application_id',
        'amount',
        'commission_amount',
        'freelancer_amount',
        'status',
        'funded_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'freelancer_amount' => 'decimal:2',
        'funded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function project()
    {
        return $this->belongsTo(UserProject::class, 'user_project_id');
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
