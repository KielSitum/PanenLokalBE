<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('buyer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('listing_id')
                ->constrained('listings')
                ->cascadeOnDelete();

            // Status transaksi manual
            $table->enum('status', [
                'negotiating', // baru klik hubungi
                'success',     // deal berhasil
                'failed'       // tidak jadi
            ])->default('negotiating');

            // Optional
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
