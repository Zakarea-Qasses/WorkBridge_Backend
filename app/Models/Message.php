<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'edited_at',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'sender_id' => 'integer',
        'edited_at' => 'datetime',
    ];

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = Crypt::encryptString($value);
    }

    public function getContentAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return Crypt::decryptString($value);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
