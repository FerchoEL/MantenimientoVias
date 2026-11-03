<?php

namespace Tests\Feature;

use App\Models\Track;
use App\Models\TrackSection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\DatabaseTestCase;

class TrackSectionCodeUniqueMigrationTest extends DatabaseTestCase
{
    public function test_migration_stops_with_duplicate_ids_and_preserves_the_table_and_name_index(): void
    {
        $migration = require database_path('migrations/2026_11_03_180740_add_track_code_unique_to_track_sections_table.php');
        $migration->down();

        $track = Track::factory()->create();
        $firstSection = TrackSection::factory()->for($track)->create(['name' => 'Primer tramo', 'code' => 'S-01']);
        $secondSection = TrackSection::factory()->for($track)->create(['name' => 'Segundo tramo', 'code' => 'S-01']);

        try {
            $migration->up();
            $this->fail('The migration must stop when duplicate codes exist.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('track_id='.$track->id, $exception->getMessage());
            $this->assertStringContainsString('code=S-01', $exception->getMessage());
            $this->assertStringContainsString('IDs=['.$firstSection->id.', '.$secondSection->id.']', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('track_sections'));
        $this->assertFalse(Schema::hasIndex('track_sections', ['track_id', 'code'], 'unique'));
        $this->assertTrue(Schema::hasIndex('track_sections', ['track_id', 'name'], 'unique'));
        $this->assertModelExists($firstSection);
        $this->assertModelExists($secondSection);
    }
}
