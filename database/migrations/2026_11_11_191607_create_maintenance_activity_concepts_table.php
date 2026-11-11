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
        Schema::create('maintenance_activity_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_activity_category_id')->constrained('maintenance_activity_categories', 'id', 'mac_concept_category_fk')->restrictOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['maintenance_activity_category_id', 'name'], 'mac_concept_category_name_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_activity_concepts');
    }
};
