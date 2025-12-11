<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    use HasFactory;

    protected $table = 'user_verifications';

    protected $fillable = [
        'user_id',
        'full_name',
        'nik',
        'address',
        'ktp_image',
        'status',
        'note',
        'submitted_at',
        'verified_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

