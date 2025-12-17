<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Tambahkan import Hash

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ❌ Hapus default factory user (atau biarkan jika ingin)
        // User::factory(10)->create(); 
        
        // 1. Akun Default Buyer (gunakan firstOrCreate untuk idempoten)
        User::firstOrCreate(
            ['email' => 'koko@example.com'],
            [
                'role' => 'buyer',
                'full_name' => 'Koko Buyer',
                'phone' => '081234567890',
                'password' => Hash::make('password'),
                'slogan' => 'Pembeli resmi panen lokal',
                'address' => 'Jakarta Selatan',
            ]
        );
        
        // 2. 🔥 AKUN ADMIN
        User::firstOrCreate(
            ['email' => 'admin@panenlokal.com'],
            [
                'role' => 'admin',
                'full_name' => 'Admin Panen Lokal',
                'phone' => '080011223344',
                'password' => Hash::make('password'),
                'slogan' => 'Pengelola sistem verifikasi',
                'address' => 'Pusat',
            ]
        );
        
        // 3. Akun Default Farmer (Verified) untuk Testing
        User::firstOrCreate(
            ['email' => 'farmer@example.com'],
            [
                'role' => 'farmer',
                'full_name' => 'Fulan Farmer',
                'phone' => '085050505050',
                'password' => Hash::make('password'),
                'slogan' => 'Petani Super Cepat',
                'address' => 'Bandung',
            ]
        );

        $this->call(UserVerificationSeeder::class);
    }
}