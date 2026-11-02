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
        Schema::create('track_switches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unique(['plant_id', 'code']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track_switches');
    }
};
