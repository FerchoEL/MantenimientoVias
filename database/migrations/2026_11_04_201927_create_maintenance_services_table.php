<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('order_number', 80)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('report_frequency', 20);
            $table->string('status', 20)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_services');
    }
};
