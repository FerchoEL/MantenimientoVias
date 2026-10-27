<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\Plant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class PlantTest extends DatabaseTestCase
{
    public function test_plant_belongs_to_its_client(): void
    {
        $client = Client::factory()->create();
        $plant = Plant::factory()->for($client)->create();

        $this->assertTrue($plant->fresh()->client->is($client));
    }

    public function test_plant_defaults_to_active_and_accepts_optional_fields(): void
    {
        $client = Client::factory()->create();
        $plant = Plant::create(['client_id' => $client->id, 'name' => 'Planta Norte'])->refresh();

        $this->assertTrue($plant->active);
        $this->assertNull($plant->short_name);
        $this->assertNull($plant->observations);
        $this->assertNotNull($plant->created_at);
        $this->assertNotNull($plant->updated_at);
    }

    public function test_optional_fields_can_be_assigned_and_cleared(): void
    {
        $client = Client::factory()->create(['short_name' => 'CN']);
        $plant = Plant::factory()->for($client)->inactive()->create([
            'short_name' => 'PN',
            'observations' => 'Acceso norte',
        ]);

        $this->assertSame('CN', $client->fresh()->short_name);
        $this->assertSame('PN', $plant->fresh()->short_name);
        $this->assertSame('Acceso norte', $plant->fresh()->observations);
        $this->assertFalse($plant->fresh()->active);

        $client->update(['short_name' => null]);
        $plant->update(['short_name' => null, 'observations' => null]);

        $this->assertNull($client->fresh()->short_name);
        $this->assertNull($plant->fresh()->short_name);
        $this->assertNull($plant->fresh()->observations);
    }

    public function test_duplicate_plant_name_is_rejected_within_a_client(): void
    {
        $client = Client::factory()->create();
        Plant::factory()->for($client)->create(['name' => 'Planta Norte']);

        try {
            Plant::factory()->for($client)->create(['name' => 'Planta Norte']);
            $this->fail('Duplicate names within a client must be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }

        $this->assertDatabaseCount('plants', 1);
    }

    public function test_same_plant_name_is_allowed_for_different_clients(): void
    {
        $first = Plant::factory()->create(['name' => 'Planta Norte']);
        $second = Plant::factory()->create(['name' => 'Planta Norte']);

        $this->assertNotSame($first->client_id, $second->client_id);
        $this->assertDatabaseCount('plants', 2);
    }

    public function test_foreign_key_rejects_a_nonexistent_client(): void
    {
        $this->expectException(QueryException::class);

        DB::table('plants')->insert(['client_id' => 999, 'name' => 'Sin cliente']);
    }

    public function test_foreign_key_is_required(): void
    {
        $this->expectException(QueryException::class);

        DB::table('plants')->insert(['name' => 'Sin cliente']);
    }

    public function test_create_rejects_an_inactive_client_without_persisting_the_plant(): void
    {
        $client = Client::factory()->inactive()->create();

        try {
            Plant::create(['client_id' => $client->id, 'name' => 'Planta Nueva']);
            $this->fail('Creating a plant for an inactive client must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'client_id' => ['No se pueden crear plantas para un cliente inactivo.'],
            ], $exception->errors());
        }

        $this->assertDatabaseCount('plants', 0);
        $this->assertFalse($client->fresh()->active);
    }

    public function test_relationship_create_rechecks_client_state_in_the_database(): void
    {
        $client = Client::factory()->create();
        Client::findOrFail($client->id)->update(['active' => false]);

        try {
            $client->plants()->create(['name' => 'Planta Nueva']);
            $this->fail('A stale client instance must not bypass the check.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('client_id', $exception->errors());
        }

        $this->assertDatabaseCount('plants', 0);
    }

    public function test_save_rechecks_a_preloaded_client_before_creating_a_plant(): void
    {
        $client = Client::factory()->create();
        $plant = new Plant(['name' => 'Planta Nueva']);
        $plant->client()->associate($client);
        Client::findOrFail($client->id)->update(['active' => false]);

        try {
            $plant->save();
            $this->fail('Saving a new plant for an inactive client must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('client_id', $exception->errors());
        }

        $this->assertDatabaseCount('plants', 0);
    }
}
