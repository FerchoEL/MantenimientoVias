<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('maintenance_service_id')
                ->constrained('maintenance_services', indexName: 'mp_service_fk')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20);
            $table->foreignId('created_by')
                ->constrained('users', indexName: 'mp_creator_fk')->restrictOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_periods');
    }
};
