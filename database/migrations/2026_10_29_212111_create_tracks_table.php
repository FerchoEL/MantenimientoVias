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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 50)->nullable();
            $table->string('sketch_disk', 40)->nullable();
            $table->string('sketch_path', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->unique(['plant_id', 'name']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
