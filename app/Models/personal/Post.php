<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'price',
        'budget',
        'address',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }


    
    public function scopeProjects($query)
    {
        return $query->where('type', 'project');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function isProject(): bool
    {
        return $this->type === 'project';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }
}