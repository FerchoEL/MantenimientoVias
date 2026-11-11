<?php

namespace Tests\Feature;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\Tracks\Pages\ListTracks;
use App\Filament\Resources\Tracks\TrackResource;
use App\Filament\Resources\TrackSwitches\Pages\CreateTrackSwitch;
use App\Filament\Resources\TrackSwitches\Pages\EditTrackSwitch;
use App\Filament\Resources\TrackSwitches\Pages\ListTrackSwitches;
use App\Filament\Resources\TrackSwitches\TrackSwitchResource;
use App\Models\Plant;
use App\Models\Track;
use App\Models\TrackSwitch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

class TrackCatalogAuthorizationAndResourcesTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_seeder_adds_only_the_four_catalog_permissions_and_remains_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = Role::findByName('Administrador', 'web');
        $jefe = Role::findByName('Jefe de supervisores', 'web');
        $supervisor = Role::findByName('Supervisor', 'web');

        $this->assertSame(45, Permission::count());
        $this->assertCount(45, $admin->permissions);
        $this->assertCount(45, $jefe->permissions);
        $this->assertCount(19, $supervisor->permissions);
        $this->assertTrue($admin->hasPermissionTo('maintenance.track.manage'));
        $this->assertTrue($jefe->hasPermissionTo('maintenance.track-switch.view'));
        $this->assertFalse($supervisor->hasPermissionTo('maintenance.track.view'));
        $this->assertFalse($supervisor->hasPermissionTo('maintenance.track-switch.manage'));
    }

    public function test_policies_allow_catalog_access_to_administrator_and_jefe_only(): void
    {
        $track = Track::factory()->create();
        $trackSwitch = TrackSwitch::factory()->for($track->plant)->create();

        foreach (['Administrador', 'Jefe de supervisores'] as $roleName) {
            $user = $this->userWithRole($roleName);

            $this->assertTrue(Gate::forUser($user)->allows('viewAny', Track::class));
            $this->assertTrue(Gate::forUser($user)->allows('view', $track));
            $this->assertTrue(Gate::forUser($user)->allows('create', Track::class));
            $this->assertTrue(Gate::forUser($user)->allows('update', $track));
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', TrackSwitch::class));
            $this->assertTrue(Gate::forUser($user)->allows('view', $trackSwitch));
            $this->assertTrue(Gate::forUser($user)->allows('create', TrackSwitch::class));
            $this->assertTrue(Gate::forUser($user)->allows('update', $trackSwitch));
            $this->assertFalse(Gate::forUser($user)->allows('delete', $track));
            $this->assertFalse(Gate::forUser($user)->allows('delete', $trackSwitch));
        }

        $supervisor = $this->userWithRole('Supervisor');

        $this->assertFalse(Gate::forUser($supervisor)->allows('viewAny', Track::class));
        $this->assertFalse(Gate::forUser($supervisor)->allows('viewAny', TrackSwitch::class));
        $this->assertFalse(TrackResource::canViewAny());
        $this->assertFalse(TrackSwitchResource::canViewAny());
    }

    public function test_supervisor_cannot_open_track_catalog_pages_or_direct_edit_urls(): void
    {
        $supervisor = $this->userWithRole('Supervisor');
        $track = Track::factory()->create();
        $trackSwitch = TrackSwitch::factory()->for($track->plant)->create();

        $this->actingAs($supervisor, 'web')->get(route('filament.admin.resources.tracks.index'))->assertForbidden();
        $this->actingAs($supervisor, 'web')->get(route('filament.admin.resources.tracks.create'))->assertForbidden();
        $this->actingAs($supervisor, 'web')->get(route('filament.admin.resources.tracks.edit', $track))->assertForbidden();
        $this->actingAs($supervisor, 'web')->get(route('filament.admin.resources.track-switches.index'))->assertForbidden();
        $this->actingAs($supervisor, 'web')->get(route('filament.admin.resources.track-switches.create'))->assertForbidden();
        $this->actingAs($supervisor, 'web')->get(route('filament.admin.resources.track-switches.edit', $trackSwitch))->assertForbidden();
    }

    public function test_resources_are_in_configuracion_and_do_not_expose_physical_deletion(): void
    {
        $track = Track::factory()->create();
        $trackSwitch = TrackSwitch::factory()->for($track->plant)->create();

        $this->assertSame('Vías', TrackResource::getNavigationLabel());
        $this->assertSame('vía', TrackResource::getModelLabel());
        $this->assertSame('Configuración', TrackResource::getNavigationGroup());
        $this->assertSame('Cambios de vía', TrackSwitchResource::getNavigationLabel());
        $this->assertSame('Configuración', TrackSwitchResource::getNavigationGroup());
        $this->assertFalse(TrackResource::canDeleteAny());
        $this->assertFalse(TrackSwitchResource::canDeleteAny());
        $admin = $this->userWithRole('Administrador');

        foreach ([Track::class, TrackSwitch::class] as $model) {
            $record = $model === Track::class ? $track : $trackSwitch;
            $this->assertFalse(Gate::forUser($admin)->allows('deleteAny', $model));
            $this->assertFalse(Gate::forUser($admin)->allows('restore', $record));
            $this->assertFalse(Gate::forUser($admin)->allows('forceDelete', $record));
            $this->assertFalse(Gate::forUser($admin)->allows('forceDeleteAny', $model));
        }
        $this->assertModelExists($track);
        $this->assertModelExists($trackSwitch);
    }

    public function test_administrator_can_create_and_edit_a_track(): void
    {
        $plant = Plant::factory()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $plant->id, 'name' => 'Vía principal', 'code' => 'VP', 'active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $track = Track::query()->where('name', 'Vía principal')->firstOrFail();

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->fillForm(['name' => 'Vía secundaria', 'code' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'plant_id' => $plant->id, 'name' => 'Vía secundaria', 'code' => null]);
    }

    public function test_track_name_is_unique_within_a_plant_but_may_repeat_in_another_plant(): void
    {
        $firstPlant = Plant::factory()->create();
        $secondPlant = Plant::factory()->create();
        Track::factory()->for($firstPlant)->create(['name' => 'Vía norte', 'code' => 'R1']);
        $this->actingAs($this->userWithRole('Jefe de supervisores'), 'web');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $firstPlant->id, 'name' => 'Vía norte'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique'])
            ->assertSee('Ya existe una vía con ese nombre para la planta seleccionada.');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $secondPlant->id, 'name' => 'Vía norte'])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $firstPlant->id, 'name' => 'Vía sur', 'code' => 'R2'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tracks', ['plant_id' => $secondPlant->id, 'name' => 'Vía norte']);
        $this->assertDatabaseHas('tracks', ['plant_id' => $firstPlant->id, 'name' => 'Vía sur', 'code' => 'R2']);
    }

    public function test_track_form_reports_required_and_maximum_length_errors_in_spanish(): void
    {
        $plant = Plant::factory()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $plant->id, 'name' => str_repeat('V', 121)])
            ->call('create')
            ->assertHasFormErrors(['name' => 'max'])
            ->assertSee('El nombre de vía no puede superar los 120 caracteres.');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => null, 'name' => null])
            ->call('create')
            ->assertHasFormErrors(['plant_id' => 'required', 'name' => 'required']);
    }

    public function test_track_creation_rejects_inactive_plant_or_client(): void
    {
        $inactivePlant = Plant::factory()->inactive()->create();
        $inactiveClientPlant = Plant::factory()->create();
        $inactiveClientPlant->client->update(['active' => false]);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        foreach ([$inactivePlant, $inactiveClientPlant] as $plant) {
            Livewire::test(CreateTrack::class)
                ->fillForm(['plant_id' => $plant->id, 'name' => 'No permitida'])
                ->call('create')
                ->assertHasErrors();
        }

        $this->assertDatabaseMissing('tracks', ['name' => 'No permitida']);
    }

    public function test_track_edit_keeps_historical_plant_but_rejects_reassignment_to_inactive_plant(): void
    {
        $track = Track::factory()->create();
        $historicPlant = $track->plant;
        $historicPlant->update(['active' => false]);
        $inactiveDestination = Plant::factory()->inactive()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->fillForm(['name' => 'Vía histórica corregida'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->fillForm(['plant_id' => $inactiveDestination->id])
            ->call('save')
            ->assertHasErrors('data.plant_id');

        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'plant_id' => $historicPlant->id, 'name' => 'Vía histórica corregida']);
    }

    public function test_jefe_can_create_and_edit_a_track_switch(): void
    {
        $plant = Plant::factory()->create();
        $this->actingAs($this->userWithRole('Jefe de supervisores'), 'web');

        Livewire::test(CreateTrackSwitch::class)
            ->fillForm(['plant_id' => $plant->id, 'code' => 'C-01', 'name' => 'Cambio norte', 'description' => 'Descripción', 'active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $trackSwitch = TrackSwitch::query()->where('code', 'C-01')->firstOrFail();

        Livewire::test(EditTrackSwitch::class, ['record' => $trackSwitch->getRouteKey()])
            ->fillForm(['code' => 'C-02', 'name' => 'Cambio actualizado', 'description' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_switches', ['id' => $trackSwitch->id, 'plant_id' => $plant->id, 'code' => 'C-02', 'name' => 'Cambio actualizado']);
    }

    public function test_track_switch_code_is_unique_within_a_plant_but_may_repeat_in_another_plant(): void
    {
        $firstPlant = Plant::factory()->create();
        $secondPlant = Plant::factory()->create();
        TrackSwitch::factory()->for($firstPlant)->create(['code' => 'C-01']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(CreateTrackSwitch::class)
            ->fillForm(['plant_id' => $firstPlant->id, 'code' => 'C-01', 'name' => 'Duplicado'])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique'])
            ->assertSee('Ya existe un cambio con ese código para la planta seleccionada.');

        Livewire::test(CreateTrackSwitch::class)
            ->fillForm(['plant_id' => $secondPlant->id, 'code' => 'C-01', 'name' => 'Otro cambio'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_switches', ['plant_id' => $secondPlant->id, 'code' => 'C-01']);
    }

    public function test_track_switch_form_reports_required_and_maximum_length_errors_in_spanish(): void
    {
        $plant = Plant::factory()->create();
        $this->actingAs($this->userWithRole('Jefe de supervisores'), 'web');

        Livewire::test(CreateTrackSwitch::class)
            ->fillForm(['plant_id' => $plant->id, 'code' => str_repeat('C', 51), 'name' => 'Cambio'])
            ->call('create')
            ->assertHasFormErrors(['code' => 'max'])
            ->assertSee('El código no puede superar los 50 caracteres.');

        Livewire::test(CreateTrackSwitch::class)
            ->fillForm(['plant_id' => null, 'code' => null, 'name' => null])
            ->call('create')
            ->assertHasFormErrors(['plant_id' => 'required', 'code' => 'required', 'name' => 'required']);
    }

    public function test_track_switch_creation_rejects_inactive_plant_or_client(): void
    {
        $inactivePlant = Plant::factory()->inactive()->create();
        $inactiveClientPlant = Plant::factory()->create();
        $inactiveClientPlant->client->update(['active' => false]);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        foreach ([$inactivePlant, $inactiveClientPlant] as $plant) {
            Livewire::test(CreateTrackSwitch::class)
                ->fillForm(['plant_id' => $plant->id, 'code' => 'C-99', 'name' => 'No permitido'])
                ->call('create')
                ->assertHasErrors();
        }

        $this->assertDatabaseMissing('track_switches', ['code' => 'C-99']);
    }

    public function test_track_switch_edit_keeps_historical_plant_but_rejects_inactive_reassignment(): void
    {
        $trackSwitch = TrackSwitch::factory()->create();
        $historicPlant = $trackSwitch->plant;
        $historicPlant->update(['active' => false]);
        $inactiveDestination = Plant::factory()->inactive()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(EditTrackSwitch::class, ['record' => $trackSwitch->getRouteKey()])
            ->fillForm(['name' => 'Cambio histórico corregido'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditTrackSwitch::class, ['record' => $trackSwitch->getRouteKey()])
            ->fillForm(['plant_id' => $inactiveDestination->id])
            ->call('save')
            ->assertHasErrors('data.plant_id');

        $this->assertDatabaseHas('track_switches', ['id' => $trackSwitch->id, 'plant_id' => $historicPlant->id, 'name' => 'Cambio histórico corregido']);
    }

    public function test_status_actions_toggle_records_without_changing_related_history(): void
    {
        $track = Track::factory()->create();
        $trackSwitch = TrackSwitch::factory()->for($track->plant)->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(ListTracks::class)
            ->callAction(TestAction::make('toggleActive')->table($track))
            ->assertNotified('Vía desactivada');
        Livewire::test(ListTrackSwitches::class)
            ->callAction(TestAction::make('toggleActive')->table($trackSwitch))
            ->assertNotified('Cambio de vía desactivado');

        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'active' => false]);
        $this->assertDatabaseHas('track_switches', ['id' => $trackSwitch->id, 'active' => false]);
        $this->assertModelExists($track->fresh());
        $this->assertModelExists($trackSwitch->fresh());
    }

    public function test_inactive_catalog_records_remain_visible_to_authorized_users(): void
    {
        $track = Track::factory()->inactive()->create(['name' => 'Vía histórica']);
        $trackSwitch = TrackSwitch::factory()->inactive()->for($track->plant)->create(['code' => 'C-H']);
        $this->actingAs($this->userWithRole('Jefe de supervisores'), 'web');

        Livewire::test(ListTracks::class)->assertCanSeeTableRecords([$track]);
        Livewire::test(ListTrackSwitches::class)->assertCanSeeTableRecords([$trackSwitch]);
    }

    public function test_edit_forms_show_non_blocking_notices_for_inactive_infrastructure(): void
    {
        $track = Track::factory()->create();
        $trackSwitch = TrackSwitch::factory()->for($track->plant)->create();
        $track->plant->client->update(['active' => false]);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->assertSee('Esta vía pertenece a una planta o cliente inactivo. La información se conserva para consulta histórica.');

        Livewire::test(EditTrackSwitch::class, ['record' => $trackSwitch->getRouteKey()])
            ->assertSee('Este cambio de vía pertenece a una planta o cliente inactivo. La información se conserva para consulta histórica.');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
