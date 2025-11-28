<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role',
        'email',
        'password',
        'full_name',
        'phone',
        'address',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role' => 'string',
    ];

    // Relations
    public function farmerVerifications()
    {
        return $this->hasMany(FarmerVerification::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'farmer_id');
    }

    public function buyerRequests()
    {
        return $this->hasMany(RequestModel::class, 'buyer_id');
    }

    public function farmerRequests()
    {
        return $this->hasMany(RequestModel::class, 'farmer_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function communityPosts()
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function communityComments()
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function analyticsSales()
    {
        return $this->hasMany(AnalyticsSale::class, 'farmer_id');
    }
}
