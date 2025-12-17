<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['buyer', 'farmer', 'admin'])->default('buyer');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('phone');
            $table->string('slogan')->nullable(); 
            $table->string('latitude')->nullable(); 
            $table->string('longitude')->nullable();
            $table->string('fcm_token')->nullable(); 
            $table->text('address')->nullable(); 
            $table->text('avatar_url')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};