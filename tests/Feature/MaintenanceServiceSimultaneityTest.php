<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use App\Filament\Resources\MaintenanceServices\Pages\ListMaintenanceServices;
use App\Filament\Resources\MaintenanceServices\Pages\ViewMaintenanceService;
use App\MaintenanceServiceStatus;
use App\Models\Client;
use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceSupervisor;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class MaintenanceServiceSimultaneityTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', config('app.timezone')));
    }

    /** @return array<string, MaintenanceService> */
    private function services(): array
    {
        $firstClient = Client::factory()->create(['name' => 'Cliente Uno']);
        $secondClient = Client::factory()->create(['name' => 'Cliente Dos']);
        $services = [];
        foreach (['A' => $firstClient, 'B' => $firstClient, 'C' => $secondClient] as $plantName => $client) {
            $plant = Plant::factory()->for($client)->create(['name' => 'Planta '.$plantName]);
            foreach ([1, 2, 3] as $number) {
                $key = $plantName.$number;
                $services[$key] = MaintenanceService::factory()->for($plant)->create([
                    'name' => 'Servicio '.$key, 'start_date' => '2026-11-01',
                ]);
            }
        }

        return $services;
    }

    private function user(string $role = 'Supervisor'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function assign(User $user, MaintenanceService $service, array $attributes = []): MaintenanceServiceSupervisor
    {
        return MaintenanceServiceSupervisor::factory()->forSupervisor($user)->for($service, 'service')->create(array_replace([
            'start_date' => '2026-11-01', 'end_date' => null, 'active' => true,
        ], $attributes));
    }

    /** @param array<MaintenanceService> $expected */
    private function assertTableIds(Testable $page, array $expected): void
    {
        $expectedIds = collect($expected)->pluck('id')->sort()->values()->all();
        $actualIds = $page->instance()->getTableRecords()->pluck('id')->sort()->values()->all();
        $this->assertSame($expectedIds, $actualIds);
    }

    public function test_partially_shared_sets_remain_exact_across_plants_clients_and_direct_urls(): void
    {
        $services = $this->services();
        $first = $this->user();
        $second = $this->user();
        $firstKeys = ['A1', 'A2', 'B1', 'C1'];
        $secondKeys = ['A2', 'C1', 'C2'];
        foreach ($firstKeys as $key) {
            $this->assign($first, $services[$key], ['end_date' => '2026-11-20']);
        }
        foreach ($secondKeys as $key) {
            $this->assign($second, $services[$key]);
        }
        $this->assign($first, $services['A1']);

        foreach ([[$first, $firstKeys], [$second, $secondKeys]] as [$user, $keys]) {
            $this->actingAs($user, 'web');
            $expected = array_intersect_key($services, array_flip($keys));
            $this->assertTableIds(Livewire::test(ListMaintenanceServices::class), $expected);
            $this->assertSame(collect($expected)->pluck('id')->sort()->values()->all(),
                MaintenanceService::query()->visibleTo($user)->orderBy('id')->pluck('id')->all());
            foreach ($services as $key => $service) {
                $response = $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $service->id]));
                if (in_array($key, $keys, true)) {
                    $response->assertOk()->assertSee($service->name)->assertSee($service->plant->name);
                } else {
                    $response->assertNotFound()->assertDontSee($service->name);
                }
            }
        }

        foreach (['Administrador', 'Jefe de supervisores'] as $role) {
            $this->actingAs($this->user($role), 'web');
            $this->assertTableIds(Livewire::test(ListMaintenanceServices::class), $services);
        }
    }

    public function test_search_filters_and_navigation_never_expand_the_assigned_set(): void
    {
        $services = $this->services();
        $user = $this->user();
        $this->assign($user, $services['A1']);
        $this->assign($user, $services['B1']);
        $this->assign($this->user(), $services['C1']);
        $this->actingAs($user, 'web');
        $expected = [$services['A1'], $services['B1']];
        $page = Livewire::test(ListMaintenanceServices::class);

        $this->assertTableIds($page->searchTable('Servicio'), $expected);
        $this->assertTableIds($page->searchTable('Servicio A2'), []);
        $page->searchTable('');
        $this->assertSame([$services['A1']->plant->client_id], array_keys($page->instance()->getTable()->getFilter('client')->getOptions()));
        $this->assertSame([$services['A1']->plant_id, $services['B1']->plant_id],
            $page->instance()->getTable()->getFilter('plant')->getRelationshipQuery()->orderBy('id')->pluck('id')->all());
        $this->assertTableIds($page->filterTable('plant', $services['A1']->plant_id), [$services['A1']]);
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $services['B1']->id]))->assertOk();
        $this->assertTableIds($page->filterTable('plant', $services['C1']->plant_id), []);
        $this->assertTableIds($page->resetTableFilters()->filterTable('client', $services['C1']->plant->client_id), []);
        $this->assertTableIds($page->resetTableFilters(), $expected);
        $this->assertSame(collect($expected)->map(fn (MaintenanceService $service): string => MaintenanceServiceResource::getUrl('view', ['record' => $service->id]))->sort()->values()->all(),
            MaintenanceServiceResource::getGlobalSearchResults('Servicio')->pluck('url')->sort()->values()->all());
        $this->assertCount(0, MaintenanceServiceResource::getGlobalSearchResults('Servicio A2'));
    }

    public function test_deactivation_and_midnight_change_only_the_corresponding_service_access(): void
    {
        $services = $this->services();
        $user = $this->user();
        $disabled = $this->assign($user, $services['A1']);
        $today = $this->assign($user, $services['B1'], ['start_date' => '2026-11-10', 'end_date' => '2026-11-10']);
        $ongoing = $this->assign($user, $services['C1']);
        $future = $this->assign($user, $services['A2'], ['start_date' => '2026-11-11']);
        $history = collect([$today, $ongoing, $future])->mapWithKeys(fn (MaintenanceServiceSupervisor $assignment): array => [$assignment->id => $assignment->fresh()->getAttributes()])->all();
        $this->actingAs($user, 'web');
        $list = Livewire::test(ListMaintenanceServices::class);
        $firstPage = Livewire::test(ViewMaintenanceService::class, ['record' => $services['A1']->id]);
        $todayPage = Livewire::test(ViewMaintenanceService::class, ['record' => $services['B1']->id]);
        $ongoingPage = Livewire::test(ViewMaintenanceService::class, ['record' => $services['C1']->id]);
        $this->assertTableIds($list, [$services['A1'], $services['B1'], $services['C1']]);

        $disabled->update(['active' => false]);
        $firstPage->call('$refresh')->assertNotFound();
        $this->assertTableIds($list->call('$refresh'), [$services['B1'], $services['C1']]);
        $this->travelTo(Carbon::parse('2026-11-10 23:59:59', config('app.timezone')));
        $todayPage->call('$refresh')->assertOk();
        $this->travelTo(Carbon::parse('2026-11-11 00:00:00', config('app.timezone')));
        $todayPage->call('$refresh')->assertNotFound();
        $ongoingPage->call('$refresh')->assertOk();
        $this->assertTableIds($list->call('$refresh'), [$services['A2'], $services['C1']]);
        foreach ([$today, $ongoing, $future] as $assignment) {
            $this->assertSame($history[$assignment->id], $assignment->fresh()->getAttributes());
        }
        $this->assertSame('2026-11-01', $disabled->fresh()->start_date->toDateString());
        $this->assertNull($disabled->fresh()->end_date);
        $this->assertSame(['planned'], MaintenanceService::query()->distinct()->pluck('status')->map(fn (MaintenanceServiceStatus $status): string => $status->value)->all());
    }

    public function test_repeated_intervals_keep_access_until_the_last_valid_assignment_ends(): void
    {
        $services = $this->services();
        $user = $this->user();
        $this->assign($user, $services['A1'], ['end_date' => '2026-11-09']);
        $this->assign($user, $services['A1'], ['active' => false]);
        $first = $this->assign($user, $services['A1'], ['end_date' => '2026-11-10']);
        $second = $this->assign($user, $services['A1'], ['start_date' => '2026-11-10', 'end_date' => '2026-11-10']);
        $this->assign($user, $services['A1'], ['start_date' => '2026-11-11']);
        $this->assign($user, $services['C1']);
        $this->actingAs($user, 'web');
        $page = Livewire::test(ViewMaintenanceService::class, ['record' => $services['A1']->id]);
        $list = Livewire::test(ListMaintenanceServices::class);
        $this->assertTableIds($list, [$services['A1'], $services['C1']]);

        $first->update(['active' => false]);
        $page->call('$refresh')->assertOk();
        $this->assertTableIds($list->call('$refresh'), [$services['A1'], $services['C1']]);
        $second->update(['active' => false]);
        $page->call('$refresh')->assertNotFound();
        $this->assertTableIds($list->call('$refresh'), [$services['C1']]);
        $this->travelTo(Carbon::parse('2026-11-11 00:00:00', config('app.timezone')));
        $this->assertTableIds($list->call('$refresh'), [$services['A1'], $services['C1']]);
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $services['A1']->id]))->assertOk();
        $this->assertDatabaseCount('maintenance_service_supervisors', 6);
    }

    /** @return array<string, array{string, MaintenanceServiceStatus}> */
    public static function terminalActions(): array
    {
        return ['complete' => ['completeService', MaintenanceServiceStatus::Completed],
            'cancel' => ['cancelService', MaintenanceServiceStatus::Cancelled]];
    }

    #[DataProvider('terminalActions')]
    public function test_terminal_transition_preserves_assignments_and_other_plant_operations(string $action, MaintenanceServiceStatus $target): void
    {
        $services = $this->services();
        $user = $this->user();
        $otherUser = $this->user();
        foreach ([$services['A1'], $services['C1']] as $service) {
            $service->transitionTo(MaintenanceServiceStatus::Active);
            $this->assign($user, $service);
        }
        $this->assign($otherUser, $services['A1']);
        $assignments = MaintenanceServiceSupervisor::query()->orderBy('id')->get()->toArray();
        $otherService = $services['C1']->fresh()->getAttributes();
        $this->actingAs($this->user('Jefe de supervisores'), 'web');

        Livewire::test(ListMaintenanceServices::class)->callAction(TestAction::make($action)->table($services['A1']));

        $this->assertSame($target, $services['A1']->fresh()->status);
        $this->assertSame($assignments, MaintenanceServiceSupervisor::query()->orderBy('id')->get()->toArray());
        $this->assertSame($otherService, $services['C1']->fresh()->getAttributes());
        $this->actingAs($user, 'web');
        $this->assertTableIds(Livewire::test(ListMaintenanceServices::class), [$services['A1'], $services['C1']]);
        $this->get(MaintenanceServiceResource::getUrl('view', ['record' => $services['A1']->id]))->assertOk();
        $this->assertFalse($user->can('operate', [$services['A1'], 'maintenance.activity.create']));
        $this->assertTrue($user->can('operate', [$services['C1'], 'maintenance.activity.create']));
        $this->assertTrue($otherUser->can('view', $services['A1']));
    }
}
