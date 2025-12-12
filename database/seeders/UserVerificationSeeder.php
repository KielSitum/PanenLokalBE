<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserVerification;

class UserVerificationSeeder extends Seeder
{
    public function run(): void
    {
        // Cari user farmer
        $farmer = User::where('role', 'farmer')->first();

        if ($farmer) {
            UserVerification::create([
                'user_id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'nik' => '1234567890123456',
                'address' => $farmer->address ?? 'Alamat farmer default',
                'ktp_image' => 'dummy.jpg',

                // SET langsung verified
                'status' => 'verified',
                'note' => null,
                'verified_at' => now(),
            ]);
        }
    }
}
