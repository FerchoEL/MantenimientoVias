<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\Plant;
use App\Models\Track;
use App\Models\TrackSection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class TrackSectionTest extends DatabaseTestCase
{
    public function test_track_has_many_sections_and_each_section_belongs_to_a_track(): void
    {
        $track = Track::factory()->create();
        $sections = TrackSection::factory()->count(2)->for($track)->create();

        $this->assertCount(2, $track->fresh()->sections);
        $this->assertTrue($sections->first()->fresh()->track->is($track));
    }

    public function test_supported_names_are_saved_exactly_without_switch_records(): void
    {
        $track = Track::factory()->create();

        $names = ['C1-C2', 'C1-V2', 'C4-Patio'];
        $sections = collect($names)
            ->map(fn (string $name): TrackSection => TrackSection::factory()->for($track)->create(['name' => $name]));

        $this->assertSame($names, $sections->pluck('name')->all());
        $this->assertSame(0, $track->plant->trackSwitches()->count());
        $this->assertFalse(method_exists(TrackSection::class, 'startSwitch'));
        $this->assertFalse(method_exists(TrackSection::class, 'endSwitch'));
    }

    public function test_section_defaults_to_active_and_accepts_nullable_code(): void
    {
        $track = Track::factory()->create();
        $section = $track->sections()->create(['name' => 'C1-C2'])->refresh();

        $this->assertTrue($section->active);
        $this->assertNull($section->code);
        $this->assertNotNull($section->created_at);
        $this->assertNotNull($section->updated_at);
    }

    public function test_section_name_is_unique_within_its_track(): void
    {
        $track = Track::factory()->create();
        TrackSection::factory()->for($track)->create(['name' => 'C1-C2']);

        $this->expectException(QueryException::class);
        TrackSection::factory()->for($track)->create(['name' => 'C1-C2']);
    }

    public function test_same_section_name_is_allowed_in_different_tracks(): void
    {
        $firstTrack = Track::factory()->create();
        $secondTrack = Track::factory()->create();
        TrackSection::factory()->for($firstTrack)->create(['name' => 'C1-C2']);

        $section = TrackSection::factory()->for($secondTrack)->create(['name' => 'C1-C2']);

        $this->assertTrue($section->track->is($secondTrack));
    }

    public function test_foreign_key_rejects_a_missing_or_null_track(): void
    {
        try {
            DB::table('track_sections')->insert(['track_id' => 999, 'name' => 'Sin vía']);
            $this->fail('A section with a missing track must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('track_sections', 0);
        }

        $this->expectException(QueryException::class);
        DB::table('track_sections')->insert(['name' => 'Sin vía requerida']);
    }

    public function test_section_creation_is_rejected_when_client_is_inactive(): void
    {
        $track = Track::factory()->create();
        Client::findOrFail($track->plant->client_id)->update(['active' => false]);

        $this->expectException(ValidationException::class);
        TrackSection::factory()->for($track)->create();
    }

    public function test_section_creation_is_rejected_when_plant_is_inactive(): void
    {
        $plant = Plant::factory()->create();
        $track = Track::factory()->for($plant)->create();
        $plant->update(['active' => false]);

        $this->expectException(ValidationException::class);
        TrackSection::factory()->for($track)->create();
    }

    public function test_section_creation_is_rejected_when_track_is_inactive(): void
    {
        $track = Track::factory()->inactive()->create();

        $this->expectException(ValidationException::class);
        TrackSection::factory()->for($track)->create();
    }

    public function test_deactivating_client_plant_or_track_preserves_existing_sections(): void
    {
        $track = Track::factory()->create();
        $section = TrackSection::factory()->for($track)->create();
        $plant = $track->plant;
        $client = $plant->client;

        $client->update(['active' => false]);
        $this->assertTrue($plant->fresh()->active);
        $this->assertTrue($track->fresh()->active);
        $this->assertTrue($section->fresh()->active);

        $plant->update(['active' => false]);
        $this->assertTrue($track->fresh()->active);
        $this->assertTrue($section->fresh()->active);

        $track->update(['active' => false]);

        $this->assertModelExists($section->fresh());
        $this->assertTrue($section->fresh()->active);
        $this->assertSame(1, $track->fresh()->sections()->count());

        $section->update(['code' => 'S1']);

        $this->assertSame('S1', $section->fresh()->code);
    }
}
