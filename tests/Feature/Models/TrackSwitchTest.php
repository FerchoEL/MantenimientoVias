<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\Plant;
use App\Models\TrackSwitch;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class TrackSwitchTest extends DatabaseTestCase
{
    public function test_plant_has_many_track_switches_and_each_switch_belongs_to_one_plant(): void
    {
        $plant = Plant::factory()->create();
        $switches = TrackSwitch::factory()->count(2)->for($plant)->create();

        $this->assertCount(2, $plant->fresh()->trackSwitches);
        $this->assertTrue($switches->first()->fresh()->plant->is($plant));
    }

    public function test_track_switch_defaults_to_active_and_accepts_nullable_description(): void
    {
        $plant = Plant::factory()->create();
        $trackSwitch = $plant->trackSwitches()->create(['code' => 'C1', 'name' => 'Cambio 1'])->refresh();

        $this->assertTrue($trackSwitch->active);
        $this->assertNull($trackSwitch->description);
        $this->assertNotNull($trackSwitch->created_at);
        $this->assertNotNull($trackSwitch->updated_at);
    }

    public function test_track_switch_code_is_unique_within_its_plant(): void
    {
        $plant = Plant::factory()->create();
        TrackSwitch::factory()->for($plant)->create(['code' => 'C1']);

        $this->expectException(QueryException::class);
        TrackSwitch::factory()->for($plant)->create(['code' => 'C1']);
    }

    public function test_same_track_switch_code_is_allowed_in_different_plants(): void
    {
        $firstPlant = Plant::factory()->create();
        $secondPlant = Plant::factory()->create();
        TrackSwitch::factory()->for($firstPlant)->create(['code' => 'C1']);

        $trackSwitch = TrackSwitch::factory()->for($secondPlant)->create(['code' => 'C1']);

        $this->assertTrue($trackSwitch->plant->is($secondPlant));
    }

    public function test_foreign_key_rejects_a_missing_or_null_plant(): void
    {
        try {
            DB::table('track_switches')->insert(['plant_id' => 999, 'code' => 'C1', 'name' => 'Cambio inexistente']);
            $this->fail('A switch with a missing plant must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('track_switches', 0);
        }

        $this->expectException(QueryException::class);
        DB::table('track_switches')->insert(['code' => 'C1', 'name' => 'Cambio sin planta']);
    }

    public function test_track_switch_creation_is_rejected_when_its_client_is_inactive(): void
    {
        $plant = Plant::factory()->create();
        Client::findOrFail($plant->client_id)->update(['active' => false]);

        $this->expectException(ValidationException::class);
        TrackSwitch::factory()->for($plant)->create();
    }

    public function test_track_switch_creation_is_rejected_when_its_plant_is_inactive(): void
    {
        $plant = Plant::factory()->inactive()->create();

        $this->expectException(ValidationException::class);
        TrackSwitch::factory()->for($plant)->create();
    }
}
