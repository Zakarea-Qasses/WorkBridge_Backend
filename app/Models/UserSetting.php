<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'profile_visible',
        'contact_permission',
        'message_notifications',
    ];

    protected $casts = [
        'profile_visible' => 'boolean',
        'message_notifications' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
