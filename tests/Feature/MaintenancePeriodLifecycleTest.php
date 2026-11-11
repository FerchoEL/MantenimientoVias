<?php

namespace Tests\Feature;

use App\MaintenancePeriodLifecycle;
use App\MaintenancePeriodStatus;
use App\MaintenanceServiceStatus;
use App\Models\MaintenancePeriod;
use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceSupervisor;
use App\Models\User;
use App\ReportFrequency;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\DatabaseTestCase;

class MaintenancePeriodLifecycleTest extends DatabaseTestCase
{
    private function manager(string $role = 'Administrador'): User
    {
        $this->seed(RolePermissionSeeder::class);

        return User::factory()->create()->assignRole($role);
    }

    private function service(array $attributes = []): MaintenanceService
    {
        return MaintenanceService::factory()->create(['status' => MaintenanceServiceStatus::Active, ...$attributes]);
    }

    private function open(User $actor, MaintenanceService $service, string $start = '2026-11-05', string $end = '2026-11-11'): MaintenancePeriod
    {
        return app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, ['start_date' => $start, 'end_date' => $end]);
    }

    public function test_open_sets_creator_and_open_status_and_ignores_injected_fields(): void
    {
        $actor = $this->manager();
        $service = $this->service();
        $period = app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, [
            'start_date' => '2026-11-05', 'end_date' => '2026-11-11',
            'created_by' => 99999, 'status' => 'closed', 'closed_at' => now(), 'maintenance_service_id' => 99999,
        ])->refresh();
        $this->assertSame($actor->id, $period->created_by);
        $this->assertSame($service->id, $period->maintenance_service_id);
        $this->assertSame(MaintenancePeriodStatus::Open, $period->status);
        $this->assertNull($period->closed_at);
        $this->assertNull($period->approved_at);
        $this->assertNull($period->submitted_at);
        $this->assertDatabaseCount('maintenance_service_supervisors', 0);
    }

    public static function missingRecords(): array
    {
        return ['open' => ['open'], 'close' => ['close']];
    }

    #[DataProvider('missingRecords')]
    public function test_missing_record_is_rejected(string $operation): void
    {
        $actor = $this->manager();
        $this->expectException(ModelNotFoundException::class);
        app(MaintenancePeriodLifecycle::class)->{$operation}($actor, 99999, []);
    }

    public static function unavailableServices(): array
    {
        return ['planned' => [MaintenanceServiceStatus::Planned], 'completed' => [MaintenanceServiceStatus::Completed], 'cancelled' => [MaintenanceServiceStatus::Cancelled]];
    }

    #[DataProvider('unavailableServices')]
    public function test_open_requires_active_service(MaintenanceServiceStatus $status): void
    {
        $actor = $this->manager();
        $service = $this->service(['status' => $status]);
        $this->assertRejected(fn () => $this->open($actor, $service), 'service');
        $this->assertDatabaseCount('maintenance_periods', 0);
    }

    public function test_open_requires_active_client(): void
    {
        $actor = $this->manager();
        $service = $this->service();
        $service->plant->client->update(['active' => false]);
        $this->assertRejected(fn () => $this->open($actor, $service), 'service');
        $this->assertDatabaseCount('maintenance_periods', 0);
    }

    public static function invalidDates(): array
    {
        return [
            'missing start' => [null, '2026-11-11', 'start_date'],
            'missing end' => ['2026-11-05', null, 'end_date'],
            'invalid calendar' => ['2026-02-30', '2026-11-11', 'start_date'],
            'invalid format' => ['tomorrow', '2026-11-11', 'start_date'],
            'reversed' => ['2026-11-11', '2026-11-05', 'end_date'],
            'before service' => ['2026-11-04', '2026-11-11', 'dates'],
            'after service' => ['2026-11-05', '2027-01-01', 'dates'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_open_rejects_invalid_dates(?string $start, ?string $end, string $field): void
    {
        $actor = $this->manager();
        $service = $this->service(['end_date' => '2026-12-31']);
        $this->assertRejected(fn () => app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, ['start_date' => $start, 'end_date' => $end]), $field);
        $this->assertDatabaseCount('maintenance_periods', 0);
    }

    public static function overlaps(): array
    {
        return [
            'duplicate' => ['2026-11-10', '2026-11-20'],
            'touch start' => ['2026-11-05', '2026-11-10'],
            'touch end' => ['2026-11-20', '2026-11-25'],
            'contained' => ['2026-11-12', '2026-11-18'],
            'enclosing' => ['2026-11-05', '2026-11-25'],
        ];
    }

    #[DataProvider('overlaps')]
    public function test_overlap_is_inclusive_and_preserves_existing_period(string $start, string $end): void
    {
        $actor = $this->manager();
        $service = $this->service();
        $existing = $this->open($actor, $service, '2026-11-10', '2026-11-20')->refresh()->getAttributes();
        $this->assertRejected(fn () => $this->open($actor, $service, $start, $end), 'dates');
        $this->assertSame($existing, MaintenancePeriod::sole()->getAttributes());
    }

    public static function manualFrequencies(): array
    {
        return ['weekly' => [ReportFrequency::Weekly], 'monthly' => [ReportFrequency::Monthly], 'none' => [ReportFrequency::None]];
    }

    #[DataProvider('manualFrequencies')]
    public function test_manual_periods_allow_one_day_gaps_and_variable_duration(ReportFrequency $frequency): void
    {
        $actor = $this->manager('Jefe de supervisores');
        $service = $this->service(['report_frequency' => $frequency, 'end_date' => '2026-12-31']);
        $first = $this->open($actor, $service, '2026-11-05', '2026-11-05');
        $next = $this->open($actor, $service, '2026-12-01', '2026-12-31');
        $this->assertNotSame($first->id, $next->id);
        $this->assertSame(2, $service->periods()->count());
    }

    public function test_different_services_can_have_identical_dates(): void
    {
        $actor = $this->manager();
        $first = $this->open($actor, $this->service());
        $second = $this->open($actor, $this->service());
        $this->assertNotSame($first->maintenance_service_id, $second->maintenance_service_id);
        $this->assertDatabaseCount('maintenance_periods', 2);
    }

    public function test_biweekly_sequence_crosses_month_and_year_boundaries(): void
    {
        $actor = $this->manager();
        $service = $this->service(['start_date' => '2026-12-20', 'report_frequency' => ReportFrequency::Biweekly]);
        $first = $this->open($actor, $service, '2026-12-20', '2027-01-03');
        $next = $this->open($actor, $service, '2027-01-04', '2027-01-18');
        $this->assertSame('2027-01-03', $first->end_date->toDateString());
        $this->assertSame('2027-01-04', $next->start_date->toDateString());
        $this->assertSame(2, $service->periods()->count());
    }

    public static function invalidQuincenas(): array
    {
        return [
            'short' => ['2026-11-05', '2026-11-18', false, null, 'end_date'],
            'long' => ['2026-11-05', '2026-11-20', false, null, 'end_date'],
            'wrong anchor' => ['2026-11-06', '2026-11-20', false, null, 'start_date'],
            'skip first' => ['2026-11-20', '2026-12-04', false, null, 'start_date'],
            'gap' => ['2026-12-05', '2026-12-19', true, null, 'start_date'],
            'partial beyond service' => ['2026-11-20', '2026-12-04', true, '2026-11-30', 'dates'],
            'short last partial' => ['2026-11-20', '2026-11-30', true, '2026-11-30', 'end_date'],
        ];
    }

    #[DataProvider('invalidQuincenas')]
    public function test_biweekly_invalid_sequence_is_rejected(string $start, string $end, bool $previous, ?string $serviceEnd, string $field): void
    {
        $actor = $this->manager();
        $service = $this->service(['report_frequency' => ReportFrequency::Biweekly, 'end_date' => $serviceEnd]);
        if ($previous) {
            $this->open($actor, $service, '2026-11-05', '2026-11-19');
        }
        $this->assertRejected(fn () => $this->open($actor, $service, $start, $end), $field);
        $this->assertSame($previous ? 1 : 0, $service->periods()->count());
    }

    public static function closableServices(): array
    {
        $cases = [];
        foreach (MaintenanceServiceStatus::cases() as $status) {
            foreach ([true, false] as $active) {
                $cases[$status->value.($active ? ' active client' : ' inactive client')] = [$status, $active];
            }
        }

        return $cases;
    }

    #[DataProvider('closableServices')]
    public function test_close_approved_preserves_history_regardless_of_service_or_client_state(MaintenanceServiceStatus $status, bool $active): void
    {
        $actor = $this->manager();
        $service = $this->service(['status' => $status]);
        $period = MaintenancePeriod::factory()->for($service, 'service')->create(['status' => MaintenancePeriodStatus::Approved, 'approved_at' => '2026-11-10 10:00:00'])->refresh();
        $original = $period->getAttributes();
        $service->plant->client->update(['active' => $active]);
        $this->freezeTime();
        $closed = app(MaintenancePeriodLifecycle::class)->close($actor, $period->id)->refresh();
        $this->assertSame(MaintenancePeriodStatus::Closed, $closed->status);
        $this->assertSame(now()->toDateTimeString(), $closed->closed_at->toDateTimeString());
        foreach (['maintenance_service_id', 'created_by', 'start_date', 'end_date', 'submitted_at', 'approved_at', 'created_at'] as $field) {
            $this->assertSame($original[$field], $closed->getAttributes()[$field]);
        }
        $this->assertSame($status, $service->fresh()->status);
        $this->assertDatabaseCount('maintenance_service_supervisors', 0);
    }

    public static function unclosableStatuses(): array
    {
        return ['open' => [MaintenancePeriodStatus::Open], 'submitted' => [MaintenancePeriodStatus::Submitted], 'changes' => [MaintenancePeriodStatus::ChangesRequested], 'closed' => [MaintenancePeriodStatus::Closed]];
    }

    #[DataProvider('unclosableStatuses')]
    public function test_close_rejects_other_states_without_changing_history(MaintenancePeriodStatus $status): void
    {
        $actor = $this->manager();
        $period = MaintenancePeriod::factory()->create(['status' => $status])->refresh();
        $original = $period->getAttributes();
        $this->assertRejected(fn () => app(MaintenancePeriodLifecycle::class)->close($actor, $period->id), 'status');
        $this->assertSame($original, $period->fresh()->getAttributes());
    }

    public function test_duplicate_close_keeps_original_timestamp_and_stale_model_cannot_overwrite_closed_record(): void
    {
        $actor = $this->manager();
        $stale = MaintenancePeriod::factory()->create(['status' => MaintenancePeriodStatus::Approved]);
        $closed = app(MaintenancePeriodLifecycle::class)->close($actor, $stale->id)->refresh();
        $original = $closed->getAttributes();
        $this->travel(1)->day();
        $this->assertRejected(fn () => app(MaintenancePeriodLifecycle::class)->close($actor, $stale->id), 'status');
        $this->assertRejected(fn () => $stale->update(['end_date' => '2026-12-01']), 'period');
        $this->assertRejected(fn () => $closed->update(['status' => MaintenancePeriodStatus::Open]), 'period');
        $this->assertSame($original, $closed->fresh()->getAttributes());
    }

    public static function deniedAccess(): array
    {
        return [
            'supervisor no permission' => ['Supervisor', false, true],
            'supervisor no assignment' => ['Supervisor', true, false],
            'admin missing period permission' => ['Administrador', false, false],
        ];
    }

    #[DataProvider('deniedAccess')]
    public function test_open_and_close_require_permission_and_service_access(string $role, bool $permission, bool $assigned): void
    {
        $actor = $this->manager($role);
        $service = $this->service();
        if ($permission) {
            $actor->givePermissionTo('maintenance.period.manage');
        } else {
            $actor->roles->each(fn ($role) => $role->revokePermissionTo('maintenance.period.manage'));
        }
        if ($assigned) {
            MaintenanceServiceSupervisor::factory()->forSupervisor($actor)->for($service, 'service')->create(['start_date' => now()->subDay(), 'end_date' => null]);
        }
        $period = MaintenancePeriod::factory()->for($service, 'service')->create(['status' => MaintenancePeriodStatus::Approved]);
        foreach (['open', 'close'] as $operation) {
            try {
                app(MaintenancePeriodLifecycle::class)->{$operation}($actor, $operation === 'open' ? $service->id : $period->id, ['start_date' => '2026-12-01', 'end_date' => '2026-12-05']);
                $this->fail('Unauthorized operation succeeded.');
            } catch (AuthorizationException) {
                $this->assertSame(MaintenancePeriodStatus::Approved, $period->fresh()->status);
                $this->assertSame(1, $service->periods()->count());
            }
        }
    }

    public function test_assigned_supervisor_with_explicit_permission_can_operate_but_revoked_assignment_is_rechecked(): void
    {
        $actor = $this->manager('Supervisor')->givePermissionTo('maintenance.period.manage');
        $service = $this->service();
        $assignment = MaintenanceServiceSupervisor::factory()->forSupervisor($actor)->for($service, 'service')->create(['start_date' => now()->subDay(), 'end_date' => null]);
        $period = $this->open($actor, $service);
        $period->update(['status' => MaintenancePeriodStatus::Approved]);
        $assignment->update(['active' => false]);
        $this->expectException(AuthorizationException::class);
        app(MaintenancePeriodLifecycle::class)->close($actor, $period->id);
    }

    #[DataProvider('missingRecords')]
    public function test_failure_after_persistence_rolls_back_operation(string $operation): void
    {
        $actor = $this->manager();
        $service = $this->service();
        $period = $operation === 'close' ? MaintenancePeriod::factory()->for($service, 'service')->create(['status' => MaintenancePeriodStatus::Approved])->refresh() : null;
        $original = $period?->getAttributes();
        $event = 'eloquent.'.($operation === 'open' ? 'created' : 'updated').': '.MaintenancePeriod::class;
        Event::listen($event, function (): void {
            throw new RuntimeException('Injected failure');
        });
        try {
            app(MaintenancePeriodLifecycle::class)->{$operation}($actor, $period?->id ?? $service->id, ['start_date' => '2026-11-05', 'end_date' => '2026-11-11']);
            $this->fail('Expected persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame($period ? 1 : 0, $service->periods()->count());
        $this->assertSame($original, $period?->fresh()->getAttributes());
    }

    private function assertRejected(callable $operation, string $field): void
    {
        try {
            $operation();
            $this->fail('Expected validation rejection.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
