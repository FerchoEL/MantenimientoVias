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
        Schema::create('track_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 50)->nullable();
            $table->foreignId('start_switch_id')->nullable()->constrained('track_switches')->restrictOnDelete();
            $table->foreignId('end_switch_id')->nullable()->constrained('track_switches')->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->unique(['track_id', 'name']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track_sections');
    }
};
