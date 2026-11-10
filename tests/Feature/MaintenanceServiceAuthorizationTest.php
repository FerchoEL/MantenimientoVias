<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use App\Filament\Resources\MaintenanceServices\Pages\CreateMaintenanceService;
use App\Filament\Resources\MaintenanceServices\Pages\EditMaintenanceService;
use App\Filament\Resources\MaintenanceServices\Pages\ListMaintenanceServices;
use App\Filament\Resources\MaintenanceServices\Pages\ViewMaintenanceService;
use App\MaintenanceServiceStatus;
use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceSupervisor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

class MaintenanceServiceAuthorizationTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', config('app.timezone')));
    }

    private function login(string $role = 'Supervisor'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user, 'web');

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function assign(User $user, MaintenanceService $service, array $attributes = []): MaintenanceServiceSupervisor
    {
        return MaintenanceServiceSupervisor::factory()->forSupervisor($user)->for($service, 'service')->create($attributes);
    }

    /** @return array<string, array{string, ?string, bool, bool}> */
    public static function dates(): array
    {
        return [
            'one day today' => ['2026-11-10', '2026-11-10', true, true],
            'past start open end' => ['2026-10-01', null, true, true],
            'future end' => ['2026-11-01', '2026-11-11', true, true],
            'future start open end' => ['2026-11-11', null, true, false],
            'expired' => ['2026-11-01', '2026-11-09', true, false],
            'disabled open end' => ['2026-11-01', null, false, false],
            'disabled today' => ['2026-11-10', '2026-11-10', false, false],
        ];
    }

    #[DataProvider('dates')]
    public function test_policy_and_query_enforce_same_inclusive_assignment(string $start, ?string $end, bool $active, bool $allowed): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create();
        $this->assign($user, $service, ['start_date' => $start, 'end_date' => $end, 'active' => $active]);

        $this->assertSame($allowed, Gate::forUser($user)->allows('view', $service));
        $this->assertSame($allowed ? [$service->id] : [], MaintenanceServiceResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_day_boundaries_use_application_timezone_without_browser_date_override(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create();
        $this->assign($user, $service, ['start_date' => '2026-11-10', 'end_date' => '2026-11-10']);
        $this->travelTo(Carbon::parse('2026-11-09 23:59:59', config('app.timezone')));
        $this->assertFalse($user->can('view', $service));
        $this->travelTo(Carbon::parse('2026-11-10 00:00:00', config('app.timezone')));
        $this->assertTrue($user->can('view', $service));
        $this->travelTo(Carbon::parse('2026-11-10 23:59:59', config('app.timezone')));
        $this->assertTrue($user->can('view', $service));
        $this->travelTo(Carbon::parse('2026-11-11 00:00:00', config('app.timezone')));

        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $service->id, 'date' => '2026-11-10']))->assertNotFound();
        $this->assertModelExists($service);
    }

    public function test_multiple_services_clients_and_supervisors_do_not_duplicate_or_expand_scope(): void
    {
        $user = $this->login();
        $services = MaintenanceService::factory()->count(2)->create();
        $otherSupervisor = User::factory()->create();
        $otherSupervisor->assignRole('Supervisor');
        foreach ($services as $service) {
            $this->assign($user, $service);
            $this->assign($user, $service);
            $this->assign($otherSupervisor, $service);
        }
        $unassigned = MaintenanceService::factory()->for($services[0]->plant)->create();
        $foreign = MaintenanceService::factory()->create();
        $this->assign($otherSupervisor, $foreign);

        Livewire::test(ListMaintenanceServices::class)->assertCanSeeTableRecords($services)
            ->assertCountTableRecords(2)->assertCanNotSeeTableRecords([$unassigned, $foreign]);
        $this->assertNotSame($services[0]->plant->client_id, $services[1]->plant->client_id);
        $this->assertTrue($otherSupervisor->can('view', $services[0]));
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $unassigned->id]))->assertNotFound();
    }

    public function test_conditions_cannot_be_combined_across_assignments_users_or_services(): void
    {
        $user = $this->login();
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Supervisor');
        $service = MaintenanceService::factory()->create();
        $otherService = MaintenanceService::factory()->create();
        $this->assign($user, $service, ['end_date' => '2026-11-09']);
        $this->assign($user, $service, ['start_date' => '2026-11-11']);
        $this->assign($user, $service, ['active' => false]);
        $this->assign($otherUser, $service);
        $this->assign($user, $otherService);

        $this->assertFalse($user->can('view', $service));
        Livewire::test(ListMaintenanceServices::class)->assertCanNotSeeTableRecords([$service])->assertCanSeeTableRecords([$otherService]);
    }

    /** @return array<string, array{string}> */
    public static function administrators(): array
    {
        return ['admin' => ['Administrador'], 'chief' => ['Jefe de supervisores']];
    }

    #[DataProvider('administrators')]
    public function test_administrative_roles_take_precedence_and_still_need_permissions(string $role): void
    {
        $user = $this->login($role);
        $user->assignRole('Supervisor');
        $service = MaintenanceService::factory()->create(['status' => 'cancelled']);

        Livewire::test(ListMaintenanceServices::class)->assertCanSeeTableRecords([$service]);
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $service->id]))->assertOk();
        $this->assertTrue($user->can('update', $service));
        $this->assertFalse($user->can('delete', $service));
        Role::findByName($role, 'web')->revokePermissionTo('maintenance.service.manage');
        $user->unsetRelation('roles');
        $this->assertFalse($user->can('update', $service));
        Role::findByName($role, 'web')->revokePermissionTo('maintenance.service.view');
        Role::findByName('Supervisor', 'web')->revokePermissionTo('maintenance.service.view');
        $user->unsetRelation('roles');
        $this->assertFalse($user->can('view', $service));
    }

    public function test_assignment_without_permission_or_supervisor_role_never_grants_access(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create();
        $assignment = $this->assign($user, $service);
        Role::findByName('Supervisor', 'web')->revokePermissionTo('maintenance.service.view');
        $user->unsetRelation('roles');
        $this->get(MaintenanceServiceResource::getUrl())->assertForbidden();
        $this->assertFalse($user->can('view', $service));
        $user->givePermissionTo('maintenance.service.view');
        $user->removeRole('Supervisor');

        $this->assertFalse($user->can('viewAny', MaintenanceService::class));
        $this->assertFalse($user->can('view', $service));
        $this->assertSame([], MaintenanceService::query()->visibleTo($user)->pluck('id')->all());
        $this->assertModelExists($assignment);
    }

    public function test_supervisor_cannot_administer_even_with_manage_permission_and_assignment(): void
    {
        $user = $this->login();
        $user->givePermissionTo('maintenance.service.manage');
        $service = MaintenanceService::factory()->create();
        $assignment = $this->assign($user, $service);
        foreach (['update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse($user->can($ability, $service));
        }
        foreach (['create', 'deleteAny', 'restoreAny', 'forceDeleteAny'] as $ability) {
            $this->assertFalse($user->can($ability, MaintenanceService::class));
        }
        Livewire::test(CreateMaintenanceService::class)->assertForbidden();
        Livewire::test(EditMaintenanceService::class, ['record' => $service->id])->assertForbidden();
        Livewire::test(ViewMaintenanceService::class, ['record' => $service->id])->assertActionHidden('edit');
        Livewire::test(ListMaintenanceServices::class)
            ->assertActionHidden('create')->assertTableActionHidden('edit', $service)
            ->assertTableActionHidden('startService', $service)
            ->call('mountAction', 'startService', [], ['table' => true, 'recordKey' => $service->id])
            ->call('callMountedAction');

        $this->assertSame(MaintenanceServiceStatus::Planned, $service->fresh()->status);
        $this->assertTrue($assignment->fresh()->active);
        $this->assertDatabaseCount('maintenance_service_supervisors', 1);
    }

    public function test_global_search_filters_and_tampered_identifiers_do_not_reveal_foreign_data(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create(['name' => 'Servicio autorizado']);
        $foreign = MaintenanceService::factory()->create(['name' => 'Servicio privado']);
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Supervisor');
        $this->assign($user, $service);
        $this->assign($otherUser, $foreign);
        $list = Livewire::test(ListMaintenanceServices::class)->assertCanSeeTableRecords([$service])->assertCanNotSeeTableRecords([$foreign]);
        $clientOptions = $list->instance()->getTable()->getFilter('client')->getOptions();
        $plantOptions = $list->instance()->getTable()->getFilter('plant')->getRelationshipQuery()->pluck('id')->all();
        $this->assertArrayHasKey($service->plant->client_id, $clientOptions);
        $this->assertArrayNotHasKey($foreign->plant->client_id, $clientOptions);
        $this->assertSame([$service->plant_id], $plantOptions);
        $this->assertSame(['Servicio autorizado'], MaintenanceServiceResource::getGlobalSearchResults('Servicio')->pluck('title')->all());
        $list->filterTable('plant', $foreign->plant_id)->assertCountTableRecords(0);
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $foreign->id, 'user_id' => $otherUser->id,
            'maintenance_service_id' => $service->id]))->assertNotFound()->assertDontSee('Servicio privado');
        Livewire::test(ViewMaintenanceService::class, ['record' => $foreign->id])->assertNotFound();
        Livewire::test(ListMaintenanceServices::class)
            ->call('mountAction', 'startService', [], ['table' => true, 'recordKey' => $foreign->id])
            ->call('callMountedAction');
        $this->assertSame(MaintenanceServiceStatus::Planned, $foreign->fresh()->status);
    }

    public function test_authorized_read_only_page_renders_service_and_escapes_notes(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create(['notes' => '<script>alert(1)</script>']);
        $this->assign($user, $service);

        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $service->id]))->assertOk()
            ->assertSee($service->name)->assertSee($service->plant->name)->assertSee($service->plant->client->name)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    /** @return array<string, array{string}> */
    public static function revocations(): array
    {
        return ['disabled' => ['disabled'], 'expired' => ['expired'], 'role' => ['role'], 'permission' => ['permission']];
    }

    #[DataProvider('revocations')]
    public function test_open_page_reauthorizes_on_next_livewire_request(string $revocation): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create();
        $assignment = $this->assign($user, $service, ['end_date' => '2026-11-10']);
        $page = Livewire::test(ViewMaintenanceService::class, ['record' => $service->id])->assertOk();
        $list = Livewire::test(ListMaintenanceServices::class)->assertCanSeeTableRecords([$service]);
        match ($revocation) {
            'disabled' => $assignment->update(['active' => false]),
            'expired' => $this->travelTo(Carbon::parse('2026-11-11 00:00:00', config('app.timezone'))),
            'role' => $user->removeRole('Supervisor'),
            'permission' => Role::findByName('Supervisor', 'web')->revokePermissionTo('maintenance.service.view'),
        };
        $this->actingAs($user->fresh(), 'web');

        $page->call('$refresh')->assertNotFound();
        if (in_array($revocation, ['disabled', 'expired'], true)) {
            $list->call('$refresh')->assertCanNotSeeTableRecords([$service]);
        } else {
            $list->call('$refresh')->assertForbidden();
        }
        $this->assertModelExists($assignment);
        $this->assertSame(MaintenanceServiceStatus::Planned, $service->fresh()->status);
    }

    /** @return array<string, array{string, bool}> */
    public static function statuses(): array
    {
        return ['planned' => ['planned', false], 'active' => ['active', true],
            'completed' => ['completed', false], 'cancelled' => ['cancelled', false]];
    }

    #[DataProvider('statuses')]
    public function test_read_access_is_separate_from_new_operation_prerequisites(string $status, bool $operational): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create(['status' => $status, 'start_date' => '2026-10-01', 'end_date' => '2026-10-02']);
        $assignment = $this->assign($user, $service);
        $this->assertTrue($user->can('view', $service));
        $this->assertSame($operational, $user->can('operate', [$service, 'maintenance.activity.create']));
        $this->assertFalse($user->can('operate', [$service, 'maintenance.material.create']));
        $this->assertSame($status, $service->fresh()->status->value);
        $this->assertTrue($assignment->fresh()->active);
    }

    public function test_operation_prerequisites_use_persisted_status_and_client_activity(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create(['status' => 'active']);
        $this->assign($user, $service);
        $service->plant->client->update(['active' => false]);
        $this->assertTrue($user->can('view', $service));
        $this->assertFalse($user->can('operate', [$service, 'maintenance.activity.create']));
        $service->plant->client->update(['active' => true]);
        $service->fresh()->transitionTo(MaintenanceServiceStatus::Completed);
        $this->assertFalse($user->can('operate', [$service, 'maintenance.activity.create']));
    }

    public function test_browser_cannot_replace_locked_record_on_open_page(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create();
        $foreign = MaintenanceService::factory()->create();
        $this->assign($user, $service);
        $page = Livewire::test(ViewMaintenanceService::class, ['record' => $service->id]);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $page->set('record', $foreign->id);
    }

    public function test_list_and_global_search_do_not_query_assignments_per_service(): void
    {
        $user = $this->login();
        $services = MaintenanceService::factory()->count(5)->create();
        foreach ($services as $service) {
            $this->assign($user, $service);
        }
        $queries = [];
        DB::listen(function ($event) use (&$queries): void {
            if (str_contains($event->sql, 'maintenance_service_supervisors')) {
                $queries[] = $event->sql;
            }
        });

        Livewire::test(ListMaintenanceServices::class)->assertCanSeeTableRecords($services);
        $this->assertLessThanOrEqual(5, count($queries));
        $queries = [];
        $this->assertCount(5, MaintenanceServiceResource::getGlobalSearchResults('Servicio'));
        $this->assertCount(1, $queries);
    }

    public function test_guest_cannot_query_or_view_service(): void
    {
        $service = MaintenanceService::factory()->create();
        $this->assertSame([], MaintenanceService::query()->visibleTo(null)->pluck('id')->all());
        $this->assertFalse(Gate::allows('view', $service));
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $service->id]))->assertRedirect(route('filament.admin.auth.login'));
    }
}
