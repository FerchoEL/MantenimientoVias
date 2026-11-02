<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\Plant;
use App\Models\Track;
use App\Models\TrackSwitch;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class TrackTest extends DatabaseTestCase
{
    public function test_plant_has_many_tracks_and_each_track_belongs_to_one_plant(): void
    {
        $plant = Plant::factory()->create();
        $tracks = Track::factory()->count(2)->for($plant)->create();

        $this->assertCount(2, $plant->fresh()->tracks);
        $this->assertTrue($tracks->first()->fresh()->plant->is($plant));
    }

    public function test_track_defaults_to_active_and_accepts_nullable_fields(): void
    {
        $plant = Plant::factory()->create();
        $track = $plant->tracks()->create(['name' => 'Vía Norte'])->refresh();

        $this->assertTrue($track->active);
        $this->assertNull($track->code);
        $this->assertNull($track->sketch_disk);
        $this->assertNull($track->sketch_path);
        $this->assertNotNull($track->created_at);
        $this->assertNotNull($track->updated_at);
    }

    public function test_track_name_is_unique_within_its_plant(): void
    {
        $plant = Plant::factory()->create();
        Track::factory()->for($plant)->create(['name' => 'Vía Norte']);

        $this->expectException(QueryException::class);
        Track::factory()->for($plant)->create(['name' => 'Vía Norte']);
    }

    public function test_same_track_name_is_allowed_in_different_plants(): void
    {
        $firstPlant = Plant::factory()->create();
        $secondPlant = Plant::factory()->create();
        Track::factory()->for($firstPlant)->create(['name' => 'Vía Norte']);

        $track = Track::factory()->for($secondPlant)->create(['name' => 'Vía Norte']);

        $this->assertTrue($track->plant->is($secondPlant));
    }

    public function test_foreign_key_rejects_a_missing_or_null_plant(): void
    {
        try {
            DB::table('tracks')->insert(['plant_id' => 999, 'name' => 'Vía inexistente']);
            $this->fail('A track with a missing plant must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('tracks', 0);
        }

        $this->expectException(QueryException::class);
        DB::table('tracks')->insert(['name' => 'Vía sin planta']);
    }

    public function test_track_creation_is_rejected_when_its_client_is_inactive(): void
    {
        $plant = Plant::factory()->create();
        Client::findOrFail($plant->client_id)->update(['active' => false]);

        $this->expectException(ValidationException::class);
        Track::factory()->for($plant)->create();
    }

    public function test_track_creation_is_rejected_when_its_plant_is_inactive(): void
    {
        $plant = Plant::factory()->inactive()->create();

        $this->expectException(ValidationException::class);
        Track::factory()->for($plant)->create();
    }

    public function test_deactivating_a_plant_preserves_active_tracks_and_switches(): void
    {
        $plant = Plant::factory()->create();
        $track = Track::factory()->for($plant)->create();
        $trackSwitch = TrackSwitch::factory()->for($plant)->create();

        $plant->update(['active' => false]);

        $this->assertModelExists($track->fresh());
        $this->assertModelExists($trackSwitch->fresh());
        $this->assertTrue($track->fresh()->active);
        $this->assertTrue($trackSwitch->fresh()->active);
        $this->assertSame(1, $plant->fresh()->tracks()->count());
        $this->assertSame(1, $plant->fresh()->trackSwitches()->count());
    }

    public function test_deactivating_a_client_preserves_the_active_hierarchy_and_switch_code(): void
    {
        $plant = Plant::factory()->create();
        $track = Track::factory()->for($plant)->create();
        $trackSwitch = TrackSwitch::factory()->for($plant)->create(['code' => 'C-01']);

        Client::findOrFail($plant->client_id)->update(['active' => false]);

        $this->assertTrue($plant->fresh()->active);
        $this->assertTrue($track->fresh()->active);
        $this->assertTrue($trackSwitch->fresh()->active);
        $this->assertSame('C-01', $trackSwitch->fresh()->code);
    }

    public function test_track_does_not_own_track_switches(): void
    {
        $this->assertFalse(method_exists(Track::class, 'trackSwitches'));
        $this->assertFalse(method_exists(TrackSwitch::class, 'track'));
    }

    public function test_plant_cannot_be_deleted_while_tracks_or_switches_exist(): void
    {
        $plant = Plant::factory()->create();
        Track::factory()->for($plant)->create();
        TrackSwitch::factory()->for($plant)->create();

        $this->expectException(QueryException::class);
        $plant->delete();
    }
}
