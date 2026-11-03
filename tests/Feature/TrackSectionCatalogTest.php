<?php

namespace Tests\Feature;

use App\Filament\Resources\Tracks\Pages\CreateTrack;
use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\TrackSections\Pages\CreateTrackSection;
use App\Filament\Resources\TrackSections\Pages\EditTrackSection;
use App\Filament\Resources\TrackSections\Pages\ListTrackSections;
use App\Filament\Resources\TrackSections\TrackSectionResource;
use App\Models\Track;
use App\Models\TrackSection;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

class TrackSectionCatalogTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_catalog_is_independent_and_authorized_with_existing_track_permissions(): void
    {
        $track = Track::factory()->create();
        $section = TrackSection::factory()->for($track)->create();

        foreach (['Administrador', 'Jefe de supervisores'] as $roleName) {
            $user = $this->userWithRole($roleName);
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', TrackSection::class));
            $this->assertTrue(Gate::forUser($user)->allows('view', $section));
            $this->assertTrue(Gate::forUser($user)->allows('create', TrackSection::class));
            $this->assertTrue(Gate::forUser($user)->allows('update', $section));
        }

        $supervisor = $this->userWithRole('Supervisor');
        $this->assertFalse(Gate::forUser($supervisor)->allows('viewAny', TrackSection::class));
        $this->assertFalse(TrackSectionResource::canViewAny());
        $this->assertSame('Tramos', TrackSectionResource::getNavigationLabel());
        $this->assertSame('Configuración', TrackSectionResource::getNavigationGroup());
        $this->assertFalse(TrackSectionResource::canDeleteAny());

        $this->actingAs($supervisor, 'web')
            ->get(route('filament.admin.resources.track-sections.index'))
            ->assertForbidden();
        $this->get(route('filament.admin.resources.track-sections.create'))->assertForbidden();
        $this->get(route('filament.admin.resources.track-sections.edit', $section))->assertForbidden();
        Livewire::test(ListTrackSections::class)->assertForbidden();

        $this->actingAs($this->userWithRole('Administrador'), 'web');
        Livewire::test(ListTrackSections::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$section])
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete');
        Livewire::test(EditTrackSection::class, ['record' => $section->getRouteKey()])
            ->assertOk()
            ->assertFormFieldDisabled('track_id');
    }

    public function test_administrator_can_create_edit_search_and_filter_sections_without_reassigning_track(): void
    {
        $track = Track::factory()->create();
        $otherTrack = Track::factory()->create();
        $section = TrackSection::factory()->for($track)->create(['name' => 'C1-C2', 'code' => 'S-01']);
        $otherSection = TrackSection::factory()->for($otherTrack)->create(['name' => 'C4-Patio', 'code' => 'S-04']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(CreateTrackSection::class)
            ->fillForm(['track_id' => $track->id, 'name' => 'C1-V2', 'code' => 'S-02'])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = TrackSection::query()->where('name', 'C1-V2')->firstOrFail();
        $this->assertSame($track->id, $created->track_id);

        Livewire::test(EditTrackSection::class, ['record' => $section->getRouteKey()])
            ->fillForm(['name' => 'C1-C2 Ajustado', 'code' => 'S-01A'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_sections', [
            'id' => $section->id,
            'track_id' => $track->id,
            'name' => 'C1-C2 Ajustado',
            'code' => 'S-01A',
        ]);

        Livewire::test(ListTrackSections::class)
            ->assertCanSeeTableRecords([$section, $otherSection, $created])
            ->assertSee($track->plant->client->name)
            ->assertSee($track->plant->name)
            ->assertSee($track->name);

        Livewire::test(ListTrackSections::class)
            ->searchTable('S-04')
            ->assertCanSeeTableRecords([$otherSection])
            ->assertCanNotSeeTableRecords([$section, $created]);

        Livewire::test(ListTrackSections::class)
            ->searchTable('C1-C2 Ajustado')
            ->assertCanSeeTableRecords([$section])
            ->assertCanNotSeeTableRecords([$otherSection]);

        Livewire::test(ListTrackSections::class)
            ->filterTable('plant', $track->plant_id)
            ->assertCanSeeTableRecords([$section, $created])
            ->assertCanNotSeeTableRecords([$otherSection]);

        Livewire::test(ListTrackSections::class)
            ->filterTable('track', $track->id)
            ->assertCanSeeTableRecords([$section, $created])
            ->assertCanNotSeeTableRecords([$otherSection]);

        Livewire::test(ListTrackSections::class)
            ->callAction(TestAction::make('toggleActive')->table($created))
            ->assertNotified('Tramo desactivado');

        $this->assertDatabaseHas('track_sections', ['id' => $created->id, 'active' => false]);

        Livewire::test(ListTrackSections::class)
            ->filterTable('active', false)
            ->assertCanSeeTableRecords([$created])
            ->assertCanNotSeeTableRecords([$section, $otherSection]);
    }

    public function test_independent_form_rejects_inactive_infrastructure_and_duplicate_names(): void
    {
        $inactiveClientOwner = Track::factory()->create();
        $inactiveClientOwner->plant->client->update(['active' => false]);

        $inactivePlantOwner = Track::factory()->create();
        $inactivePlantOwner->plant->update(['active' => false]);

        $inactiveTrack = Track::factory()->create();
        $inactiveTrack->update(['active' => false]);

        $track = Track::factory()->create();
        TrackSection::factory()->for($track)->create(['name' => 'Tramo repetido']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        foreach ([$inactiveClientOwner, $inactivePlantOwner, $inactiveTrack] as $inactiveOwner) {
            Livewire::test(CreateTrackSection::class)
                ->fillForm(['track_id' => $inactiveOwner->id, 'name' => 'Alta no permitida'])
                ->call('create')
                ->assertHasFormErrors();
        }

        Livewire::test(CreateTrackSection::class)
            ->fillForm(['track_id' => $track->id, 'name' => 'Tramo repetido'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique'])
            ->assertSee('Ya existe un tramo con ese nombre para la vía seleccionada.');
    }

    public function test_independent_form_validates_optional_codes_on_create_and_edit(): void
    {
        $track = Track::factory()->create();
        $otherTrack = Track::factory()->create();
        $firstSection = TrackSection::factory()->for($track)->create(['name' => 'Tramo A', 'code' => 'S-01']);
        TrackSection::factory()->for($track)->create(['name' => 'Tramo B', 'code' => 'S-02']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(CreateTrackSection::class)
            ->fillForm(['track_id' => $track->id, 'name' => 'Código repetido', 'code' => 'S-01'])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique'])
            ->assertSee('Ya existe un tramo con ese código para la vía seleccionada.');

        Livewire::test(CreateTrackSection::class)
            ->fillForm(['track_id' => $otherTrack->id, 'name' => 'Código en otra vía', 'code' => 'S-01'])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateTrackSection::class)
            ->fillForm(['track_id' => $track->id, 'name' => 'Sin código A', 'code' => null])
            ->call('create')
            ->assertHasNoFormErrors();
        Livewire::test(CreateTrackSection::class)
            ->fillForm(['track_id' => $track->id, 'name' => 'Sin código B', 'code' => null])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(EditTrackSection::class, ['record' => $firstSection->getRouteKey()])
            ->fillForm(['code' => 'S-01'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditTrackSection::class, ['record' => $firstSection->getRouteKey()])
            ->fillForm(['code' => 'S-02'])
            ->call('save')
            ->assertHasFormErrors(['code' => 'unique'])
            ->assertSee('Ya existe un tramo con ese código para la vía seleccionada.');
    }

    public function test_track_code_is_optional_unique_per_plant_and_self_is_ignored_on_edit(): void
    {
        $track = Track::factory()->create(['code' => 'VP-01']);
        $samePlant = Track::factory()->for($track->plant)->create(['code' => null]);
        $samePlantWithOtherCode = Track::factory()->for($track->plant)->create(['code' => 'VP-02']);
        $otherPlant = Track::factory()->create(['code' => 'VP-01']);
        $this->assertDatabaseHas('tracks', ['id' => $otherPlant->id, 'code' => 'VP-01']);
        $this->assertNull($samePlant->code);
        $this->assertDatabaseHas('tracks', ['id' => $samePlantWithOtherCode->id, 'code' => 'VP-02']);

        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $track->plant_id, 'name' => 'Vía duplicada', 'code' => 'VP-01'])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique'])
            ->assertSee('Ya existe una vía con ese código para la planta seleccionada.');

        Livewire::test(CreateTrack::class)
            ->fillForm(['plant_id' => $track->plant_id, 'name' => 'Vía sin código', 'code' => null])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->fillForm(['code' => 'VP-01'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->fillForm(['code' => 'VP-02'])
            ->call('save')
            ->assertHasFormErrors(['code' => 'unique'])
            ->assertSee('Ya existe una vía con ese código para la planta seleccionada.');

        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'code' => 'VP-01']);
    }

    public function test_incremental_index_rejects_duplicate_non_null_track_codes_in_one_plant(): void
    {
        $track = Track::factory()->create(['code' => 'VP-01']);

        $this->expectException(QueryException::class);

        Track::factory()->for($track->plant)->create(['code' => 'VP-01']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
