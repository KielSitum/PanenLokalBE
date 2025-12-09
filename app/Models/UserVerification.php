<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\UserVerificationer;
use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    public function verification() {
    return $this->hasOne(UserVerification::class);
}
}
