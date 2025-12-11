<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'title',
        'description',
        'price',
        'stock',
        'category',
        // Kolom BARU
        'location',
        'area',
        'type',
        'contact_name',
        'contact_number',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }

    public function analyticsSales()
    {
        return $this->hasMany(AnalyticsSale::class);
    }
}