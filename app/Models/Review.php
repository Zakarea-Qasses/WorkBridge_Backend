<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'contract_id',
        'reviewer_id',
        'reviewed_user_id',
        'rating',
        'comment',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_user_id');
    }

    protected static function booted()
    {
        static::saved(function (Review $review) {
            static::updateProfileRating($review->reviewed_user_id);

            if ($review->wasChanged('reviewed_user_id')) {
                static::updateProfileRating($review->getOriginal('reviewed_user_id'));
            }
        });

        static::deleted(function (Review $review) {
            static::updateProfileRating($review->reviewed_user_id);
        });
    }

    public static function updateProfileRating(?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $profile = Profile::where('user_id', $userId)->first();

        if (! $profile) {
            return;
        }

        $avg = static::where('reviewed_user_id', $userId)
            ->whereHas('contract', function ($query) use ($userId) {
                $query->where('freelancer_id', $userId);
            })
            ->avg('rating');

        $profile->update([
            'rating_avg' => round((float) $avg, 2),
        ]);
    }
}
