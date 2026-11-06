<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use App\Filament\Resources\MaintenanceServices\Pages\CreateMaintenanceService;
use App\Filament\Resources\MaintenanceServices\Pages\EditMaintenanceService;
use App\Filament\Resources\MaintenanceServices\Pages\ListMaintenanceServices;
use App\MaintenanceServiceStatus;
use App\Models\Client;
use App\Models\MaintenanceService;
use App\Models\Plant;
use App\Models\User;
use App\ReportFrequency;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class MaintenanceServiceResourceTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function login(string $role = 'Administrador'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user, 'web');

        return $user;
    }

    /** @return array<string, mixed> */
    private function input(Plant $plant): array
    {
        return ['client_id' => $plant->client_id, 'plant_id' => $plant->id, 'name' => 'Servicio repetible',
            'order_number' => 'PED-1', 'start_date' => '2026-11-05', 'end_date' => null, 'report_frequency' => 'biweekly'];
    }

    /** @return array<string, array{string}> */
    public static function administrators(): array
    {
        return ['admin' => ['Administrador'], 'jefe' => ['Jefe de supervisores']];
    }

    #[DataProvider('administrators')]
    public function test_admin_roles_can_access_create_and_edit(string $role): void
    {
        $user = $this->login($role);
        $service = MaintenanceService::factory()->create();
        $this->assertTrue(MaintenanceServiceResource::canAccess());
        $this->assertNotEmpty(MaintenanceServiceResource::getNavigationItems());
        foreach (['view', 'update'] as $ability) {
            $this->assertTrue(Gate::forUser($user)->allows($ability, $service));
        }
        foreach (['viewAny', 'create'] as $ability) {
            $this->assertTrue(Gate::forUser($user)->allows($ability, MaintenanceService::class));
        }
        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(MaintenanceServiceResource::getUrl($page, ['record' => $service->id]))->assertOk();
        }
        Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($service->plant))
            ->call('create')->assertHasNoFormErrors();
        Livewire::test(EditMaintenanceService::class, ['record' => $service->id])
            ->fillForm(['name' => 'Corregido'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Corregido', $service->fresh()->name);
    }

    public function test_supervisor_is_denied_even_with_service_view_permission(): void
    {
        $user = $this->login('Supervisor');
        $service = MaintenanceService::factory()->create();
        $this->assertTrue($user->can('maintenance.service.view'));
        $this->assertFalse(MaintenanceServiceResource::canAccess());
        $this->get(route('filament.admin.pages.dashboard'))->assertOk()->assertDontSee(MaintenanceServiceResource::getUrl(), false);
        foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, $service));
        }
        foreach (['viewAny', 'create', 'deleteAny'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, MaintenanceService::class));
        }
        foreach (['index', 'create', 'edit'] as $page) {
            $this->get(MaintenanceServiceResource::getUrl($page, ['record' => $service->id]))->assertForbidden();
        }
        Livewire::test(ListMaintenanceServices::class)->assertForbidden();
        Livewire::test(CreateMaintenanceService::class)->assertForbidden();
        Livewire::test(EditMaintenanceService::class, ['record' => $service->id])->assertForbidden();
    }

    public function test_write_actions_reauthorize_after_role_revocation(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create();
        $create = Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($service->plant));
        $edit = Livewire::test(EditMaintenanceService::class, ['record' => $service->id])->fillForm(['name' => 'Intrusión']);
        $user->syncRoles(['Supervisor']);
        $user->unsetRelation('roles');
        $create->call('create')->assertForbidden();
        $edit->call('save')->assertForbidden();
        $this->assertDatabaseCount('maintenance_services', 1);
        $this->assertNotSame('Intrusión', $service->fresh()->name);
    }

    public function test_create_forces_planned_and_allows_duplicate_names_and_orders(): void
    {
        $this->login();
        $plant = Plant::factory()->create();
        for ($i = 0; $i < 2; $i++) {
            Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($plant))
                ->set('data.status', 'active')->call('create')->assertHasNoFormErrors();
        }
        $this->assertDatabaseCount('maintenance_services', 2);
        $this->assertSame([MaintenanceServiceStatus::Planned], MaintenanceService::query()->distinct()->pluck('status')->all());
        $this->assertSame(2, $plant->maintenanceServices()->where('name', 'Servicio repetible')->where('order_number', 'PED-1')->count());
    }

    public function test_selectors_clear_plant_and_reject_another_client(): void
    {
        $this->login();
        $plant = Plant::factory()->create();
        $other = Plant::factory()->create();
        Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($plant))
            ->set('data.client_id', $other->client_id)->assertSet('data.plant_id', null)
            ->set('data.plant_id', $plant->id)->call('create')->assertHasFormErrors(['plant_id']);
        $this->assertDatabaseCount('maintenance_services', 0);
    }

    public function test_inactive_infrastructure_is_rejected(): void
    {
        $this->login();
        $inactivePlant = Plant::factory()->create(['active' => false]);
        $inactiveClient = Plant::factory()->create();
        $inactiveClient->client->update(['active' => false]);
        foreach ([$inactivePlant, $inactiveClient] as $plant) {
            Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($plant))
                ->call('create')->assertHasFormErrors();
        }
        $this->assertDatabaseCount('maintenance_services', 0);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFields(): array
    {
        return ['name required' => ['name', null], 'long name' => ['name', str_repeat('a', 151)],
            'long order' => ['order_number', str_repeat('a', 81)], 'start required' => ['start_date', null],
            'earlier end' => ['end_date', '2026-11-04'], 'frequency required' => ['report_frequency', null],
            'invalid frequency' => ['report_frequency', 'daily'], 'plant required' => ['plant_id', null],
            'client required' => ['client_id', null]];
    }

    #[DataProvider('invalidFields')]
    public function test_form_validates_fields(string $field, mixed $value): void
    {
        $this->login();
        $plant = Plant::factory()->create();
        Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($plant))
            ->set('data.'.$field, $value)->call('create')->assertHasFormErrors([$field]);
        $this->assertDatabaseCount('maintenance_services', 0);
    }

    public function test_accepts_optional_order_one_day_and_all_frequency_options(): void
    {
        $this->login();
        $plant = Plant::factory()->create();
        foreach (ReportFrequency::cases() as $frequency) {
            Livewire::test(CreateMaintenanceService::class)->fillForm(array_replace($this->input($plant), [
                'order_number' => null, 'end_date' => '2026-11-05', 'report_frequency' => $frequency->value,
            ]))->call('create')->assertHasNoFormErrors();
        }
        $this->assertSame(4, MaintenanceService::query()->whereNull('order_number')->whereDate('end_date', '2026-11-05')->count());
    }

    public function test_historical_edit_preserves_owner_and_status_despite_injected_fields(): void
    {
        $this->login();
        $service = MaintenanceService::factory()->create(['status' => MaintenanceServiceStatus::Completed]);
        $other = Plant::factory()->create();
        $service->plant->update(['active' => false]);
        $service->plant->client->update(['active' => false]);
        Livewire::test(EditMaintenanceService::class, ['record' => $service->id])
            ->assertSee('Finalizado')
            ->fillForm(['name' => 'Corrección', 'order_number' => 'PED-2', 'start_date' => '2026-11-01',
                'end_date' => '2026-11-30', 'report_frequency' => 'monthly', 'notes' => 'Nota corregida'])
            ->set('data.plant_id', $other->id)->set('data.client_id', $other->client_id)->set('data.status', 'active')
            ->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('maintenance_services', ['id' => $service->id, 'plant_id' => $service->plant_id,
            'status' => 'completed', 'name' => 'Corrección', 'order_number' => 'PED-2',
            'report_frequency' => 'monthly', 'notes' => 'Nota corregida']);
        $this->assertSame('2026-11-01', $service->fresh()->start_date->toDateString());
        $this->assertSame('2026-11-30', $service->fresh()->end_date->toDateString());
    }

    public function test_table_search_filters_and_no_delete_actions(): void
    {
        $user = $this->login();
        $service = MaintenanceService::factory()->create(['name' => 'Servicio Norte', 'order_number' => 'PED-N', 'report_frequency' => 'weekly']);
        $other = MaintenanceService::factory()->create(['name' => 'Servicio Sur', 'order_number' => 'PED-S', 'report_frequency' => 'monthly', 'status' => 'active']);
        Livewire::test(ListMaintenanceServices::class)->assertCanSeeTableRecords([$service, $other])
            ->assertSee('Semanal')->assertSee('Planeado')
            ->assertTableActionDoesNotExist('delete')->assertTableBulkActionDoesNotExist('delete');
        foreach (['Servicio Norte', 'PED-N'] as $search) {
            Livewire::test(ListMaintenanceServices::class)->searchTable($search)
                ->assertCanSeeTableRecords([$service])->assertCanNotSeeTableRecords([$other]);
        }
        foreach (['client' => $service->plant->client_id, 'plant' => $service->plant_id, 'report_frequency' => 'weekly', 'status' => 'planned'] as $filter => $value) {
            Livewire::test(ListMaintenanceServices::class)->filterTable($filter, $value)
                ->assertCanSeeTableRecords([$service])->assertCanNotSeeTableRecords([$other]);
        }
        foreach (['delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertFalse(Gate::forUser($user)->allows($ability, $service));
        }
        $this->assertFalse(MaintenanceServiceResource::canDeleteAny());
        Livewire::test(EditMaintenanceService::class, ['record' => $service->id])->assertActionDoesNotExist('delete');
    }

    public function test_selector_options_exclude_inactive_or_unrelated_infrastructure(): void
    {
        $this->login();
        $plant = Plant::factory()->create();
        $other = Plant::factory()->create();
        $inactive = Plant::factory()->for($plant->client)->create(['active' => false]);
        $inactiveClient = Client::factory()->create(['active' => false]);

        Livewire::test(CreateMaintenanceService::class)
            ->assertFormFieldExists('client_id', function (Select $field) use ($plant, $inactiveClient): bool {
                $options = $field->getOptions();

                return isset($options[$plant->client_id]) && ! isset($options[$inactiveClient->id]);
            })
            ->set('data.client_id', $plant->client_id)
            ->assertFormFieldExists('plant_id', function (Select $field) use ($plant, $other, $inactive): bool {
                $options = $field->getOptions();

                return isset($options[$plant->id]) && ! isset($options[$other->id]) && ! isset($options[$inactive->id]);
            });
    }

    public function test_creation_rechecks_client_after_form_was_opened(): void
    {
        $this->login();
        $plant = Plant::factory()->create();
        $page = Livewire::test(CreateMaintenanceService::class)->fillForm($this->input($plant));
        $plant->client->update(['active' => false]);

        $page->call('create')->assertHasFormErrors();
        $this->assertDatabaseCount('maintenance_services', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(MaintenanceServiceResource::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }
}
