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
            $table->text('location')->nullable(); 
            $table->string('area')->nullable();
            $table->enum('type', ['Timbang', 'Borong'])->default('Timbang'); 
            $table->decimal('price', 12, 2); 
            $table->decimal('stock', 10, 2)->default(0);
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