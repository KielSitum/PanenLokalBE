<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\UserVerification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role',
        'email',
        'password',
        'full_name',
        'phone',
        'slogan',
        'latitude',
        'longitude',
        'address',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
    ];
    
    // 🔥 TAMBAH: Agar atribut 'verified' selalu disertakan dalam response API
    protected $appends = ['verified'];

    protected $casts = [
        'role' => 'string',
    ];

    // Relations
    public function verification() {
        return $this->hasOne(UserVerification::class);
    }

    // 🔥 ACCESSOR BARU: Mengecek status verifikasi melalui relasi
    public function getVerifiedAttribute() {
        return $this->verification()->where('status', 'verified')->exists();
    }
    
    // ... (Relations lainnya tetap sama)

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