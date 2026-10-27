<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use App\Models\Plant;
use Illuminate\Database\QueryException;
use Tests\DatabaseTestCase;

class ClientTest extends DatabaseTestCase
{
    public function test_client_has_multiple_plants_without_including_other_clients_plants(): void
    {
        $client = Client::factory()->create();
        $plants = Plant::factory()->count(2)->for($client)->create();
        Plant::factory()->create();

        $this->assertSame($plants->modelKeys(), $client->plants()->orderBy('id')->get()->modelKeys());
    }

    public function test_client_defaults_to_active_and_accepts_an_omitted_short_name(): void
    {
        $client = Client::create(['name' => 'Cliente Norte'])->refresh();

        $this->assertTrue($client->active);
        $this->assertNull($client->short_name);
        $this->assertNotNull($client->created_at);
        $this->assertNotNull($client->updated_at);
    }

    public function test_deactivating_client_preserves_plants_states_and_historical_data(): void
    {
        $client = Client::factory()->create();
        $activePlant = Plant::factory()->for($client)->create(['observations' => 'Registro previo']);
        $inactivePlant = Plant::factory()->inactive()->for($client)->create();
        $originalPlants = $client->plants()->orderBy('id')->get()->toArray();

        $client->update(['active' => false]);

        $this->assertFalse($client->fresh()->active);
        $this->assertSame($originalPlants, $client->fresh()->plants()->orderBy('id')->get()->toArray());
        $this->assertTrue($activePlant->fresh()->active);
        $this->assertFalse($inactivePlant->fresh()->active);
        $this->assertTrue($activePlant->fresh()->client->is($client));
    }

    public function test_client_with_plants_cannot_be_deleted(): void
    {
        $client = Client::factory()->create();
        $plant = Plant::factory()->for($client)->create();

        try {
            $client->delete();
            $this->fail('A client with plants must not be deleted.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }

        $this->assertModelExists($client);
        $this->assertModelExists($plant);
    }
}
