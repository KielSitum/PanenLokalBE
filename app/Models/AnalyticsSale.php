<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'farmer_id',
        'listing_id',
        'quantity_sold',
        'total_revenue',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
