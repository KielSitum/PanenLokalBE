<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'farmer_id',
        'listing_id',
        'status',
        'contacted_at',
        'completed_at',
    ];

    // ✅ Cast timestamps
    protected $casts = [
        'contacted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // RELATIONS
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}