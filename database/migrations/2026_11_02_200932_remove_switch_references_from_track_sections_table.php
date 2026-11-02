<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasReferencedSections = DB::table('track_sections')
            ->whereNotNull('start_switch_id')
            ->orWhereNotNull('end_switch_id')
            ->exists();

        if ($hasReferencedSections) {
            throw new RuntimeException('TrackSection switch references exist and require review before removal.');
        }

        $usesForeignKeyIndexes = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::table('track_sections', function (Blueprint $table) use ($usesForeignKeyIndexes): void {
            $table->dropForeign(['start_switch_id']);
            $table->dropForeign(['end_switch_id']);

            if ($usesForeignKeyIndexes) {
                $table->dropIndex('track_sections_start_switch_id_foreign');
                $table->dropIndex('track_sections_end_switch_id_foreign');
            }

            $table->dropColumn(['start_switch_id', 'end_switch_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * The restored nullable columns cannot recover values removed by up().
     */
    public function down(): void
    {
        Schema::table('track_sections', function (Blueprint $table): void {
            $table->foreignId('start_switch_id')
                ->nullable()
                ->constrained('track_switches')
                ->restrictOnDelete();
            $table->foreignId('end_switch_id')
                ->nullable()
                ->constrained('track_switches')
                ->restrictOnDelete();
        });
    }
};
