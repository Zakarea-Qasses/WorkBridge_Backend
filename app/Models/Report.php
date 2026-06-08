<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'contract_id',
        'title',
        'category',
        'priority',
        'description',
        'attachments',
        'status',
        'admin_decision'
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function reporter(){
        return $this->belongsTo(User::class,'reporter_id');
    }

    public function contract(){
        return $this->belongsTo(Contract::class);
    }
}
