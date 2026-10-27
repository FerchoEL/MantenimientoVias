<?php

namespace Tests\Feature\Database;

use App\Models\Client;
use App\Models\Plant;
use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class ClientPlantMigrationsTest extends DatabaseTestCase
{
    public function test_client_and_plant_migrations_can_be_reversed_and_reapplied_in_memory(): void
    {
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('plants', 0);
        $clients = require database_path('migrations/2026_10_27_021628_create_clients_table.php');
        $plants = require database_path('migrations/2026_10_27_021629_create_plants_table.php');

        $plants->down();
        $clients->down();

        $this->assertFalse(Schema::hasTable('plants'));
        $this->assertFalse(Schema::hasTable('clients'));

        $clients->up();
        $plants->up();
        $client = Client::factory()->create();
        $plant = Plant::factory()->for($client)->create();

        $this->assertModelExists($plant);
        $this->assertTrue($plant->fresh()->client->is($client));
    }
}
