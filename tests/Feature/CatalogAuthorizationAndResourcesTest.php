<?php

namespace Tests\Feature;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Pages\ManageClients;
use App\Filament\Resources\Plants\Pages\ManagePlants;
use App\Filament\Resources\Plants\PlantResource;
use App\Models\Client;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

class CatalogAuthorizationAndResourcesTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_seeder_is_idempotent_and_preserves_the_existing_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(29, Permission::count());
        $this->assertCount(29, Role::findByName('Administrador', 'web')->permissions);
        $this->assertCount(29, Role::findByName('Jefe de supervisores', 'web')->permissions);
        $this->assertCount(15, Role::findByName('Supervisor', 'web')->permissions);
        $this->assertFalse(Role::findByName('Supervisor', 'web')->hasPermissionTo('maintenance.client.view'));
        $this->assertTrue(Role::findByName('Administrador', 'web')->hasPermissionTo('maintenance.plant.manage'));
    }

    public function test_policies_match_the_approved_catalog_matrix(): void
    {
        $admin = $this->userWithRole('Administrador');
        $jefe = $this->userWithRole('Jefe de supervisores');
        $supervisor = $this->userWithRole('Supervisor');
        $client = Client::factory()->create();
        $plant = Plant::factory()->for($client)->create();

        foreach ([$admin, $jefe] as $user) {
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', Client::class));
            $this->assertTrue(Gate::forUser($user)->allows('create', Client::class));
            $this->assertTrue(Gate::forUser($user)->allows('update', $client));
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', Plant::class));
            $this->assertTrue(Gate::forUser($user)->allows('create', Plant::class));
            $this->assertTrue(Gate::forUser($user)->allows('update', $plant));
            $this->assertFalse(Gate::forUser($user)->allows('delete', $client));
            $this->assertFalse(Gate::forUser($user)->allows('delete', $plant));
        }

        $this->assertFalse(Gate::forUser($supervisor)->allows('viewAny', Client::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('viewAny', Plant::class));
        $this->assertFalse(ClientResource::canViewAny());
        $this->assertFalse(PlantResource::canViewAny());
    }

    public function test_resources_are_grouped_and_exclude_physical_deletion(): void
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('Crear', __('filament-actions::create.single.modal.actions.create.label'));
        $this->assertSame('Crear y crear otro', __('filament-actions::create.single.modal.actions.create_another.label'));
        $this->assertSame('Guardar cambios', __('filament-actions::edit.single.modal.actions.save.label'));
        $this->assertSame('Configuración', ClientResource::getNavigationGroup());
        $this->assertSame('Configuración', PlantResource::getNavigationGroup());
        $this->assertFalse(ClientResource::canDeleteAny());
        $this->assertFalse(PlantResource::canDeleteAny());
    }

    public function test_supervisor_cannot_open_catalog_resources_by_direct_url(): void
    {
        $supervisor = $this->userWithRole('Supervisor');

        $this->actingAs($supervisor, 'web')
            ->get(route('filament.admin.resources.clients.index'))
            ->assertForbidden();

        $this->actingAs($supervisor, 'web')
            ->get(route('filament.admin.resources.plants.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_create_and_edit_a_client_from_the_resource(): void
    {
        $user = $this->userWithRole('Administrador');
        $this->actingAs($user, 'web');

        Livewire::test(ManageClients::class)
            ->callAction('create', [
                'name' => 'Cliente de prueba',
                'short_name' => 'Prueba',
                'active' => true,
            ])
            ->assertHasNoFormErrors();

        $client = Client::query()->where('name', 'Cliente de prueba')->firstOrFail();

        Livewire::test(ManageClients::class)
            ->callAction(TestAction::make('edit')->table($client), ['name' => 'Cliente actualizado'])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Cliente actualizado']);
    }

    public function test_authorized_user_can_create_and_edit_a_plant_and_cannot_use_an_inactive_client(): void
    {
        $user = $this->userWithRole('Jefe de supervisores');
        $client = Client::factory()->create();
        $inactiveClient = Client::factory()->inactive()->create();
        $this->actingAs($user, 'web');

        Livewire::test(ManagePlants::class)
            ->callAction('create', [
                'client_id' => $client->id,
                'name' => 'Planta de prueba',
                'short_name' => null,
                'active' => true,
                'observations' => null,
            ])
            ->assertHasNoFormErrors();

        $plant = Plant::query()->where('name', 'Planta de prueba')->firstOrFail();

        Livewire::test(ManagePlants::class)
            ->callAction(TestAction::make('edit')->table($plant), ['name' => 'Planta actualizada'])
            ->assertHasNoFormErrors();

        $this->expectException(ValidationException::class);
        $plant->update(['client_id' => $inactiveClient->id]);

        $this->assertDatabaseHas('plants', ['id' => $plant->id, 'client_id' => $client->id, 'name' => 'Planta actualizada']);
    }

    public function test_inactive_clients_are_rejected_by_the_plant_create_form(): void
    {
        $user = $this->userWithRole('Administrador');
        $inactiveClient = Client::factory()->inactive()->create();
        $this->actingAs($user, 'web');

        Livewire::test(ManagePlants::class)
            ->callAction('create', [
                'client_id' => $inactiveClient->id,
                'name' => 'Planta no permitida',
                'active' => true,
            ])
            ->assertHasErrors();

        $this->assertDatabaseMissing('plants', ['name' => 'Planta no permitida']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
