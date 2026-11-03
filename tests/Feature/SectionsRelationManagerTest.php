<?php

namespace Tests\Feature;

use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Filament\Resources\Tracks\RelationManagers\SectionsRelationManager;
use App\Models\Client;
use App\Models\Track;
use App\Models\TrackSection;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

class SectionsRelationManagerTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_relation_manager_is_rendered_inside_track_edit_page(): void
    {
        $track = Track::factory()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->assertOk()
            ->assertSeeLivewire(SectionsRelationManager::class);
    }

    public function test_manager_lists_only_sections_owned_by_its_track(): void
    {
        $track = Track::factory()->has(TrackSection::factory()->count(2), 'sections')->create();
        $otherTrack = Track::factory()->create();
        $otherSection = TrackSection::factory()->for($otherTrack)->create(['name' => 'Otro tramo']);
        $this->actingAs($this->userWithRole('Jefe de supervisores'), 'web');

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($track->sections)
            ->assertCanNotSeeTableRecords([$otherSection])
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_authorized_user_can_create_a_section_for_the_manager_owner(): void
    {
        $track = Track::factory()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'C1-C2',
                'code' => 'S-01',
                'active' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_sections', [
            'track_id' => $track->id,
            'name' => 'C1-C2',
            'code' => 'S-01',
            'active' => true,
        ]);
    }

    public function test_forged_track_id_is_ignored_and_section_cannot_be_reassigned(): void
    {
        $track = Track::factory()->create();
        $otherTrack = Track::factory()->create();
        $section = TrackSection::factory()->for($track)->create(['name' => 'C1-C2']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'track_id' => $otherTrack->id,
                'name' => 'C4-Patio',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_sections', ['track_id' => $track->id, 'name' => 'C4-Patio']);
        $this->assertDatabaseMissing('track_sections', ['track_id' => $otherTrack->id, 'name' => 'C4-Patio']);

        try {
            $section->update(['track_id' => $otherTrack->id]);
            $this->fail('A section cannot be reassigned to another track.');
        } catch (ValidationException $exception) {
            $this->assertSame('No se puede cambiar la vía propietaria de un tramo.', $exception->errors()['track_id'][0]);
        }

        $this->assertDatabaseHas('track_sections', ['id' => $section->id, 'track_id' => $track->id]);
    }

    public function test_name_is_unique_within_owner_track_and_may_repeat_on_other_tracks(): void
    {
        $track = Track::factory()->create();
        $otherTrack = Track::factory()->create();
        TrackSection::factory()->for($track)->create(['name' => 'Tramo Norte']);
        $this->actingAs($this->userWithRole('Jefe de supervisores'), 'web');

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['name' => 'Tramo Norte'])
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $otherTrack,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['name' => 'Tramo Norte'])
            ->assertHasNoFormErrors();

        $this->assertSame(2, TrackSection::query()->where('name', 'Tramo Norte')->count());
    }

    public function test_code_is_unique_within_owner_track_but_optional_and_reusable_on_other_tracks(): void
    {
        $track = Track::factory()->create();
        $otherTrack = Track::factory()->create();
        $firstSection = TrackSection::factory()->for($track)->create(['name' => 'Tramo uno', 'code' => 'S-01']);
        $secondSection = TrackSection::factory()->for($track)->create(['name' => 'Tramo dos', 'code' => 'S-02']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        $ownerManager = Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ]);

        $ownerManager->callAction(TestAction::make(CreateAction::class)->table(), [
            'name' => 'Código duplicado',
            'code' => 'S-01',
        ])
            ->assertHasFormErrors(['code' => 'Ya existe un tramo con ese código para esta vía.']);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make('edit')->table($firstSection), ['code' => 'S-01'])
            ->assertHasNoFormErrors();

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make('edit')->table($firstSection), ['code' => 'S-02'])
            ->assertHasFormErrors(['code' => 'unique']);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $otherTrack,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'Código reutilizado',
                'code' => 'S-01',
            ])
            ->assertHasNoFormErrors();

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['name' => 'Sin código uno'])
            ->assertHasNoFormErrors();
        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['name' => 'Sin código dos'])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_sections', ['id' => $secondSection->id, 'code' => 'S-02']);
        $this->assertSame(2, TrackSection::query()->where('track_id', $track->id)->whereNull('code')->count());
    }

    public function test_form_requires_name_and_enforces_name_and_code_lengths(): void
    {
        $track = Track::factory()->create();
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => str_repeat('N', 121),
                'code' => str_repeat('C', 51),
            ])
            ->assertHasFormErrors(['name' => 'max', 'code' => 'max']);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['name' => null])
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_existing_section_can_be_edited_and_activated_or_deactivated(): void
    {
        $track = Track::factory()->create();
        $section = TrackSection::factory()->for($track)->create(['name' => 'Tramo histórico']);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        $manager = Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ]);

        $manager->callAction(TestAction::make('edit')->table($section), ['name' => 'Tramo corregido'])
            ->assertHasNoFormErrors();
        $manager->callAction(TestAction::make('toggleActive')->table($section))
            ->assertNotified('Tramo desactivado');
        $manager->callAction(TestAction::make('toggleActive')->table($section))
            ->assertNotified('Tramo activado');

        $this->assertDatabaseHas('track_sections', [
            'id' => $section->id,
            'track_id' => $track->id,
            'name' => 'Tramo corregido',
            'active' => true,
        ]);
    }

    public function test_supervisor_is_denied_from_relation_manager_livewire_requests(): void
    {
        $track = Track::factory()->create();
        $this->actingAs($this->userWithRole('Supervisor'), 'web');

        $this->assertFalse(SectionsRelationManager::canViewForRecord($track, EditTrack::class));
        $this->assertFalse(Gate::allows('viewAny', TrackSection::class));

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track,
            'pageClass' => EditTrack::class,
        ])->assertForbidden();
    }

    public function test_new_sections_are_rejected_for_inactive_client_plant_or_track(): void
    {
        $user = $this->userWithRole('Administrador');
        $this->actingAs($user, 'web');

        foreach (['client', 'plant', 'track'] as $inactiveScope) {
            $track = Track::factory()->create();

            if ($inactiveScope === 'client') {
                Client::findOrFail($track->plant->client_id)->update(['active' => false]);
            } elseif ($inactiveScope === 'plant') {
                $track->plant->update(['active' => false]);
            } else {
                $track->update(['active' => false]);
            }

            Livewire::test(SectionsRelationManager::class, [
                'ownerRecord' => $track->fresh(),
                'pageClass' => EditTrack::class,
            ])
                ->callAction(TestAction::make(CreateAction::class)->table(), ['name' => "No permitido {$inactiveScope}"])
                ->assertHasErrors();

            $this->assertDatabaseMissing('track_sections', ['name' => "No permitido {$inactiveScope}"]);
        }
    }

    public function test_inactive_track_warns_and_preserves_existing_sections(): void
    {
        $track = Track::factory()->create();
        $section = TrackSection::factory()->for($track)->create(['name' => 'Tramo conservado']);
        $track->update(['active' => false]);
        $this->actingAs($this->userWithRole('Administrador'), 'web');

        Livewire::test(EditTrack::class, ['record' => $track->getRouteKey()])
            ->assertSee('Esta vía está inactiva. Consulta y corrige sus tramos históricos cuando sea necesario; no se permiten altas nuevas.')
            ->assertSeeLivewire(SectionsRelationManager::class);

        Livewire::test(SectionsRelationManager::class, [
            'ownerRecord' => $track->fresh(),
            'pageClass' => EditTrack::class,
        ])
            ->assertCanSeeTableRecords([$section])
            ->callAction(TestAction::make('edit')->table($section), ['name' => 'Tramo histórico corregido'])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('track_sections', [
            'id' => $section->id,
            'track_id' => $track->id,
            'name' => 'Tramo histórico corregido',
            'active' => true,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
