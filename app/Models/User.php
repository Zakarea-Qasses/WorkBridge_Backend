<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile(){
        return $this->hasOne(Profile::class);
    }

    public function company(){
        return $this->hasOne(Company::class);
    }

    public function wallet(){
        return $this->hasOne(Wallet::class);
    }

    public function settings(){
        return $this->hasOne(UserSetting::class);
    }

    public function clientContracts(){
        return $this->hasMany(Contract::class, 'client_id');
    }

    public function freelancerContracts(){
        return $this->hasMany(Contract::class, 'freelancer_id');
    }

    public function receivedReviews(){
        return $this->hasMany(Review::class, 'reviewed_user_id');
    }

    public function requestedCompanyDocuments()
    {
        return $this->hasMany(CompanyDocumentRequest::class, 'requested_by');
    }
}
