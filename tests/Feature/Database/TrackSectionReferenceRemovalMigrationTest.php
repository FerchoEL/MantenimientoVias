<?php

namespace Tests\Feature\Database;

use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class TrackSectionReferenceRemovalMigrationTest extends DatabaseTestCase
{
    public function test_corrective_migration_preserves_section_data_and_down_restores_empty_columns(): void
    {
        $migration = require database_path('migrations/2026_11_02_200932_remove_switch_references_from_track_sections_table.php');
        $migration->down();
        $track = Track::factory()->create();
        $sectionId = DB::table('track_sections')->insertGetId([
            'track_id' => $track->id,
            'name' => 'C1-C2',
            'code' => 'SEC-01',
            'start_switch_id' => null,
            'end_switch_id' => null,
            'active' => false,
            'created_at' => '2026-11-02 12:00:00',
            'updated_at' => '2026-11-02 12:30:00',
        ]);

        $migration->up();

        $this->assertDatabaseHas('track_sections', [
            'id' => $sectionId,
            'track_id' => $track->id,
            'name' => 'C1-C2',
            'code' => 'SEC-01',
            'active' => false,
            'created_at' => '2026-11-02 12:00:00',
            'updated_at' => '2026-11-02 12:30:00',
        ]);
        $this->assertFalse(Schema::hasColumn('track_sections', 'start_switch_id'));
        $this->assertFalse(Schema::hasColumn('track_sections', 'end_switch_id'));
        $this->assertSame([['track_id']], array_column(Schema::getForeignKeys('track_sections'), 'columns'));

        $migration->down();

        $this->assertTrue(Schema::hasColumns('track_sections', ['start_switch_id', 'end_switch_id']));
        $this->assertDatabaseHas('track_sections', [
            'id' => $sectionId,
            'start_switch_id' => null,
            'end_switch_id' => null,
        ]);
    }
}
