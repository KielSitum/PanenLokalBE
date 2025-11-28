<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('analytics_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('listing_id')->nullable()->constrained('listings')->onDelete('set null');
            $table->integer('quantity_sold');
            $table->decimal('total_revenue', 12, 2);
            $table->date('date');
        });
    }

    public function down(): void {
        Schema::dropIfExists('analytics_sales');
    }
};
