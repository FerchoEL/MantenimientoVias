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
        Schema::create('maintenance_service_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_service_id')
                ->constrained('maintenance_services')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->index(['maintenance_service_id', 'active', 'start_date', 'end_date'], 'mss_service_active_dates_index');
            $table->index(['user_id', 'active', 'start_date', 'end_date'], 'mss_user_active_dates_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_service_supervisors');
    }
};
