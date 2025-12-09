<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_verifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

    $table->string('full_name');
    $table->string('nik', 20);
    $table->text('address');
    $table->string('ktp_image');

    $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
    $table->text('note')->nullable(); // jika ditolak admin

    $table->timestamp('submitted_at')->useCurrent();
    $table->timestamp('verified_at')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_verification');
    }
};
