<?php

namespace Tests\Feature\Database;

use App\Models\Plant;
use App\Models\Track;
use App\Models\TrackSection;
use App\Models\TrackSwitch;
use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class TrackMigrationsTest extends DatabaseTestCase
{
    public function test_track_migrations_can_be_reversed_and_reapplied_in_memory(): void
    {
        $removeSwitchReferencesMigration = require database_path('migrations/2026_11_02_200932_remove_switch_references_from_track_sections_table.php');
        $trackSectionCodeUniqueMigration = require database_path('migrations/2026_11_03_180740_add_track_code_unique_to_track_sections_table.php');
        $trackCodeUniqueMigration = require database_path('migrations/2026_11_03_163105_add_plant_code_unique_to_tracks_table.php');
        $trackSectionsMigration = require database_path('migrations/2026_10_29_221616_create_track_sections_table.php');
        $trackSwitchesMigration = require database_path('migrations/2026_10_29_212112_create_track_switches_table.php');
        $tracksMigration = require database_path('migrations/2026_10_29_212111_create_tracks_table.php');

        $trackSectionCodeUniqueMigration->down();
        $removeSwitchReferencesMigration->down();
        $trackSectionsMigration->down();
        $trackSwitchesMigration->down();
        $trackCodeUniqueMigration->down();
        $tracksMigration->down();

        $this->assertFalse(Schema::hasTable('track_sections'));
        $this->assertFalse(Schema::hasTable('track_switches'));
        $this->assertFalse(Schema::hasTable('tracks'));

        $tracksMigration->up();
        $trackCodeUniqueMigration->up();
        $trackSwitchesMigration->up();
        $trackSectionsMigration->up();
        $removeSwitchReferencesMigration->up();
        $trackSectionCodeUniqueMigration->up();
        $plant = Plant::factory()->create();
        $track = Track::factory()->for($plant)->create();
        $trackSwitch = TrackSwitch::factory()->for($plant)->create();
        $trackSection = TrackSection::factory()->for($track)->create();

        $this->assertModelExists($track);
        $this->assertModelExists($trackSwitch);
        $this->assertModelExists($trackSection);
        $this->assertTrue($track->fresh()->plant->is($plant));
        $this->assertTrue($trackSwitch->fresh()->plant->is($plant));
        $this->assertFalse(Schema::hasColumn('track_switches', 'track_id'));
        $this->assertFalse(Schema::hasColumn('tracks', 'track_switch_id'));
        $this->assertFalse(Schema::hasColumn('track_sections', 'start_switch_id'));
        $this->assertFalse(Schema::hasColumn('track_sections', 'end_switch_id'));
        $this->assertTrue(Schema::hasIndex('tracks', ['plant_id', 'code'], 'unique'));
        $this->assertTrue(Schema::hasIndex('tracks', ['plant_id', 'name'], 'unique'));
        $this->assertTrue(Schema::hasIndex('track_sections', ['track_id', 'code'], 'unique'));
        $this->assertTrue(Schema::hasIndex('track_sections', ['track_id', 'name'], 'unique'));
    }
}
