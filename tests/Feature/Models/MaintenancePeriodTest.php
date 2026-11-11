<?php

namespace Tests\Feature\Models;

use App\MaintenancePeriodStatus;
use App\MaintenanceServiceStatus;
use App\Models\Client;
use App\Models\MaintenancePeriod;
use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceSupervisor;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class MaintenancePeriodTest extends DatabaseTestCase
{
    public function test_valid_period_belongs_to_service_and_creator_with_dates_and_timestamps(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', config('app.timezone')));
        $service = MaintenanceService::factory()->create();
        $creator = User::factory()->create();
        $period = MaintenancePeriod::factory()->for($service, 'service')->for($creator, 'creator')->create()->refresh();

        $this->assertTrue($period->service->is($service));
        $this->assertTrue($period->creator->is($creator));
        $this->assertSame([$period->id], $service->periods()->pluck('id')->all());
        $this->assertSame('2026-11-05', $period->start_date->toDateString());
        $this->assertSame('2026-11-11', $period->end_date->toDateString());
        $this->assertSame(MaintenancePeriodStatus::Open, $period->status);
        $this->assertNull($period->submitted_at);
        $this->assertNull($period->approved_at);
        $this->assertNull($period->closed_at);
        $this->assertSame('2026-11-10 12:00:00', $period->created_at->toDateTimeString());
        $this->assertSame('2026-11-10 12:00:00', $period->updated_at->toDateTimeString());
        $this->assertDatabaseCount('maintenance_service_supervisors', 0);
        $this->assertSame(MaintenanceServiceStatus::Planned, $service->fresh()->status);
    }

    public function test_service_has_multiple_periods_independent_of_services_in_other_plants_and_clients(): void
    {
        $firstClient = Client::factory()->create();
        $secondClient = Client::factory()->create();
        $services = collect([
            MaintenanceService::factory()->for(Plant::factory()->for($firstClient))->create(),
            MaintenanceService::factory()->for(Plant::factory()->for($firstClient))->create(),
            MaintenanceService::factory()->for(Plant::factory()->for($secondClient))->create(),
        ]);
        $creator = User::factory()->create();
        foreach ($services as $service) {
            $first = MaintenancePeriod::factory()->for($service, 'service')->for($creator, 'creator')->create([
                'start_date' => '2026-11-05', 'end_date' => '2026-11-11',
            ]);
            $next = MaintenancePeriod::factory()->for($service, 'service')->for($creator, 'creator')->create([
                'start_date' => '2026-11-12', 'end_date' => '2026-11-18',
            ]);
            $this->assertSame([$first->id, $next->id], $service->periods()->orderBy('id')->pluck('id')->all());
        }
        $this->assertDatabaseCount('maintenance_periods', 6);
        $this->assertDatabaseCount('maintenance_service_supervisors', 0);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidAttributes(): array
    {
        return [
            'missing service' => ['maintenance_service_id', null],
            'unknown service' => ['maintenance_service_id', 999999],
            'missing creator' => ['created_by', null],
            'unknown creator' => ['created_by', 999999],
            'missing start' => ['start_date', null],
            'invalid start' => ['start_date', 'not-a-date'],
            'missing end' => ['end_date', null],
            'invalid end' => ['end_date', 'not-a-date'],
            'end before start' => ['end_date', '2026-11-04'],
            'missing status' => ['status', null],
            'unknown status' => ['status', 'cancelled'],
            'invalid submission' => ['submitted_at', 'not-a-date'],
            'invalid approval' => ['approved_at', 'not-a-date'],
            'invalid closure' => ['closed_at', 'not-a-date'],
        ];
    }

    #[DataProvider('invalidAttributes')]
    public function test_eloquent_rejects_invalid_attributes_without_saving(string $field, mixed $value): void
    {
        $period = MaintenancePeriod::factory()->make();
        $period->setRawAttributes(array_replace($period->getAttributes(), [$field => $value]));

        try {
            $period->save();
            $this->fail('Invalid period was saved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertDatabaseCount('maintenance_periods', 0);
    }

    public function test_non_biweekly_period_may_start_and_end_on_the_same_day(): void
    {
        $period = MaintenancePeriod::factory()->create(['start_date' => '2026-11-05', 'end_date' => '2026-11-05'])->refresh();

        $this->assertSame('2026-11-05', $period->start_date->toDateString());
        $this->assertSame('2026-11-05', $period->end_date->toDateString());
    }

    public function test_invalid_date_correction_does_not_change_persisted_period(): void
    {
        $period = MaintenancePeriod::factory()->create()->refresh();
        $original = $period->getAttributes();

        try {
            $period->update(['end_date' => '2026-11-04']);
            $this->fail('Invalid date correction was saved.');
        } catch (ValidationException $exception) {
            $this->assertSame('La fecha final no puede ser anterior a la fecha inicial.', $exception->errors()['end_date'][0]);
        }
        $this->assertSame($original, $period->fresh()->getAttributes());
    }

    /** @return array<string, array{MaintenancePeriodStatus}> */
    public static function statuses(): array
    {
        return [
            'open' => [MaintenancePeriodStatus::Open],
            'submitted' => [MaintenancePeriodStatus::Submitted],
            'changes requested' => [MaintenancePeriodStatus::ChangesRequested],
            'approved' => [MaintenancePeriodStatus::Approved],
            'closed' => [MaintenancePeriodStatus::Closed],
        ];
    }

    #[DataProvider('statuses')]
    public function test_documented_status_values_are_cast_without_automatic_workflow(MaintenancePeriodStatus $status): void
    {
        $period = MaintenancePeriod::factory()->create(['status' => $status])->refresh();

        $this->assertSame($status, $period->status);
        $this->assertNull($period->submitted_at);
        $this->assertNull($period->approved_at);
        $this->assertNull($period->closed_at);
    }

    public function test_explicit_audit_dates_are_cast_and_timestamps_track_updates(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', config('app.timezone')));
        $period = MaintenancePeriod::factory()->create();
        $this->travelTo(Carbon::parse('2026-11-12 16:00:00', config('app.timezone')));
        $period->update(['submitted_at' => '2026-11-12 13:00:00', 'approved_at' => '2026-11-12 14:00:00', 'closed_at' => '2026-11-12 15:00:00']);
        $period->refresh();

        $this->assertSame('2026-11-12 13:00:00', $period->submitted_at->toDateTimeString());
        $this->assertSame('2026-11-12 14:00:00', $period->approved_at->toDateTimeString());
        $this->assertSame('2026-11-12 15:00:00', $period->closed_at->toDateTimeString());
        $this->assertSame('2026-11-10 12:00:00', $period->created_at->toDateTimeString());
        $this->assertSame('2026-11-12 16:00:00', $period->updated_at->toDateTimeString());
        $this->assertSame(MaintenancePeriodStatus::Open, $period->status);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidPhysicalAttributes(): array
    {
        return [
            'null service' => ['maintenance_service_id', null],
            'unknown service' => ['maintenance_service_id', 999999],
            'null creator' => ['created_by', null],
            'unknown creator' => ['created_by', 999999],
            'null start' => ['start_date', null],
            'null end' => ['end_date', null],
            'null status' => ['status', null],
        ];
    }

    #[DataProvider('invalidPhysicalAttributes')]
    public function test_database_enforces_required_columns_and_foreign_keys_without_model_events(string $field, mixed $value): void
    {
        $attributes = MaintenancePeriod::factory()->make()->getAttributes();
        $attributes[$field] = $value;

        try {
            DB::table('maintenance_periods')->insert($attributes);
            $this->fail('Invalid direct insert was accepted.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
        $this->assertDatabaseCount('maintenance_periods', 0);
    }

    /** @return array<string, array{string}> */
    public static function parents(): array
    {
        return ['service' => ['service'], 'creator' => ['creator']];
    }

    #[DataProvider('parents')]
    public function test_database_restricts_deleting_parents_of_historical_period(string $relation): void
    {
        $period = MaintenancePeriod::factory()->create()->refresh();
        $parent = $period->{$relation};

        try {
            DB::table($parent->getTable())->where('id', $parent->id)->delete();
            $this->fail('Parent with a historical period was deleted.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
        $this->assertModelExists($period);
        $this->assertModelExists($parent);
    }

    public function test_model_refuses_period_deletion_without_erasing_history(): void
    {
        $period = MaintenancePeriod::factory()->create();

        try {
            $period->delete();
            $this->fail('Historical period was deleted.');
        } catch (ValidationException $exception) {
            $this->assertSame('No se pueden eliminar periodos; se debe conservar su histórico.', $exception->errors()['period'][0]);
        }
        $this->assertModelExists($period);
    }

    public function test_assignment_rotation_and_service_completion_do_not_redefine_periods_or_grant_access(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00', config('app.timezone')));
        $first = User::factory()->create();
        $second = User::factory()->create();
        $first->assignRole('Supervisor');
        $second->assignRole('Supervisor');
        $service = MaintenanceService::factory()->create();
        $service->transitionTo(MaintenanceServiceStatus::Active);
        $period = MaintenancePeriod::factory()->for($service, 'service')->for($first, 'creator')->create()->refresh();
        $original = $period->getAttributes();
        $this->assertFalse($first->can('view', $service));
        $assignment = MaintenanceServiceSupervisor::factory()->forSupervisor($first)->for($service, 'service')->create();
        $this->assertTrue($first->can('view', $service));

        $assignment->update(['active' => false]);
        MaintenanceServiceSupervisor::factory()->forSupervisor($second)->for($service, 'service')->create();
        $service->transitionTo(MaintenanceServiceStatus::Completed);

        $this->assertFalse($first->can('view', $service));
        $this->assertTrue($second->can('view', $service));
        $this->assertSame($original, $period->fresh()->getAttributes());
        $this->assertSame([$period->id], $service->periods()->pluck('id')->all());
        $this->assertDatabaseCount('maintenance_service_supervisors', 2);
    }
}
