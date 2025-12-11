<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            
            // Kolom yang ditambahkan/diubah
            $table->text('location')->nullable(); // Alamat lengkap
            $table->string('area')->nullable(); // Luas lahan (dengan satuan m2)
            $table->enum('type', ['Timbang', 'Borong'])->default('Timbang'); // Metode penjualan
            
            // Harga dan Stok diubah ke unit yang lebih akurat
            // Akan kita simpan harga per unit (Rp/kg) jika Timbang, atau Total Harga jika Borong.
            $table->decimal('price', 12, 2); // Harga per Kg (Timbang) / Total Harga (Borong)
            $table->decimal('stock', 10, 2)->default(0); // Kuantitas dalam Ton

            // Kolom Kontak
            $table->string('contact_name')->nullable();
            $table->string('contact_number')->nullable();
            
            $table->enum('category', ['sayur', 'buah', 'organik'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('listings');
    }
};