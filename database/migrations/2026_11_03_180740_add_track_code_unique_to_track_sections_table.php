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
        $duplicateGroups = DB::table('track_sections')
            ->select('track_id', 'code')
            ->selectRaw('COUNT(*) AS total')
            ->whereNotNull('code')
            ->groupBy('track_id', 'code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isNotEmpty()) {
            $duplicateDetails = $duplicateGroups->map(function (object $duplicate): string {
                $sectionIds = DB::table('track_sections')
                    ->where('track_id', $duplicate->track_id)
                    ->where('code', $duplicate->code)
                    ->orderBy('id')
                    ->pluck('id')
                    ->implode(', ');

                return "track_id={$duplicate->track_id}, code={$duplicate->code}, IDs=[{$sectionIds}], total={$duplicate->total}";
            })->implode('; ');

            throw new RuntimeException(
                'No se puede crear UNIQUE(track_id, code) en track_sections. '
                .'Corrija manualmente los códigos duplicados antes de aplicar la migración. '
                .'Duplicados detectados: '.$duplicateDetails,
            );
        }

        Schema::table('track_sections', function (Blueprint $table) {
            $table->unique(['track_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('track_sections', function (Blueprint $table) {
            $table->dropUnique(['track_id', 'code']);
        });
    }
};
