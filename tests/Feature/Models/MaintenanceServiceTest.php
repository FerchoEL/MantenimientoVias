<?php

namespace Tests\Feature\Models;

use App\MaintenanceServiceStatus;
use App\Models\MaintenanceService;
use App\Models\Plant;
use App\ReportFrequency;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class MaintenanceServiceTest extends DatabaseTestCase
{
    public function test_plant_has_simultaneous_services_with_repeated_names_and_orders(): void
    {
        $plant = Plant::factory()->create();
        $services = MaintenanceService::factory()->for($plant)->count(2)->create([
            'name' => 'Mantenimiento', 'order_number' => 'Contrato 1',
            'status' => MaintenanceServiceStatus::Active,
            'start_date' => '2026-11-05', 'end_date' => '2026-12-19',
        ]);
        MaintenanceService::factory()->create();

        $this->assertSame($services->modelKeys(), $plant->maintenanceServices()->orderBy('id')->pluck('id')->all());
        $this->assertTrue($services->first()->plant->is($plant));
    }

    public function test_defaults_nullable_fields_casts_and_timestamps_persist(): void
    {
        $service = MaintenanceService::factory()->create()->refresh();

        $this->assertSame(MaintenanceServiceStatus::Planned, $service->status);
        $this->assertSame(ReportFrequency::Weekly, $service->report_frequency);
        $this->assertSame('2026-11-05', $service->start_date->toDateString());
        $this->assertNull($service->end_date);
        $this->assertNull($service->order_number);
        $this->assertNull($service->notes);
        $this->assertNotNull($service->created_at);
        $this->assertNotNull($service->updated_at);
        $this->assertDatabaseHas('maintenance_services', ['id' => $service->id, 'status' => 'planned', 'report_frequency' => 'weekly']);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFields(): array
    {
        return [
            'missing name' => ['name', null],
            'empty name' => ['name', ''],
            'long name' => ['name', str_repeat('a', 151)],
            'long order' => ['order_number', str_repeat('b', 81)],
            'missing start' => ['start_date', null],
            'invalid start' => ['start_date', 'not-a-date'],
            'invalid end' => ['end_date', 'not-a-date'],
            'earlier end' => ['end_date', '2026-11-04'],
            'missing frequency' => ['report_frequency', null],
            'invalid frequency' => ['report_frequency', 'daily'],
            'missing status' => ['status', null],
            'invalid status' => ['status', 'unknown'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_model_rejects_invalid_fields_without_persisting(string $field, mixed $value): void
    {
        $service = MaintenanceService::factory()->make();
        $attributes = $service->getAttributes();
        $attributes[$field] = $value;
        $service->setRawAttributes($attributes);

        try {
            $service->save();
            $this->fail('Invalid service was saved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertDatabaseCount('maintenance_services', 0);
    }

    /** @return array<string, array{?string}> */
    public static function validEnds(): array
    {
        return ['open' => [null], 'one day' => ['2026-11-05'], 'later' => ['2026-12-19']];
    }

    #[DataProvider('validEnds')]
    public function test_accepts_valid_date_boundaries_and_maximum_lengths(?string $end): void
    {
        $service = MaintenanceService::factory()->create([
            'name' => str_repeat('a', 150), 'order_number' => str_repeat('b', 80), 'end_date' => $end,
        ])->refresh();

        $this->assertSame($end, $service->end_date?->toDateString());
        $this->assertSame(150, strlen($service->name));
        $this->assertSame(80, strlen($service->order_number));
    }

    public function test_rejects_incoherent_dates_on_edit_without_changing_stored_dates(): void
    {
        $service = MaintenanceService::factory()->create(['end_date' => '2026-11-19']);
        try {
            $service->update(['start_date' => '2026-11-20']);
            $this->fail('Invalid edit was saved.');
        } catch (ValidationException $exception) {
            $this->assertSame('La fecha final no puede ser anterior a la fecha inicial.', $exception->errors()['end_date'][0]);
        }
        $this->assertSame('2026-11-05', $service->fresh()->start_date->toDateString());
    }

    /** @return array<string, array{string, string}> */
    public static function domains(): array
    {
        return [
            'weekly planned' => ['weekly', 'planned'],
            'biweekly active' => ['biweekly', 'active'],
            'monthly completed' => ['monthly', 'completed'],
            'none cancelled' => ['none', 'cancelled'],
        ];
    }

    #[DataProvider('domains')]
    public function test_persists_all_enum_values(string $frequency, string $status): void
    {
        $service = MaintenanceService::factory()->create(['report_frequency' => $frequency, 'status' => $status])->refresh();
        $this->assertSame(ReportFrequency::from($frequency), $service->report_frequency);
        $this->assertSame(MaintenanceServiceStatus::from($status), $service->status);
    }

    /** @return array<string, array{bool}> */
    public static function inactiveParents(): array
    {
        return ['plant' => [false], 'client' => [true]];
    }

    #[DataProvider('inactiveParents')]
    public function test_rejects_creation_but_preserves_history_after_parent_deactivation(bool $client): void
    {
        $plant = Plant::factory()->create();
        $historic = MaintenanceService::factory()->for($plant)->create();
        $new = MaintenanceService::factory()->for($plant)->make();
        $new->load('plant.client');
        $parent = $client ? $plant->client : $plant;
        $parent->update(['active' => false]);

        try {
            $new->save();
            $this->fail('Inactive infrastructure accepted a new service.');
        } catch (ValidationException $exception) {
            $this->assertSame('No se pueden crear servicios para una planta o cliente inactivo.', $exception->errors()['plant_id'][0]);
        }
        $this->assertDatabaseCount('maintenance_services', 1);
        $this->assertSame(MaintenanceServiceStatus::Planned, $historic->fresh()->status);
        $this->assertSame($historic->id, $plant->maintenanceServices()->sole()->id);
    }

    /** @return array<string, array{?int}> */
    public static function invalidPlantIds(): array
    {
        return ['required' => [null], 'nonexistent' => [999999]];
    }

    #[DataProvider('invalidPlantIds')]
    public function test_database_requires_existing_plant(?int $id): void
    {
        $attributes = MaintenanceService::factory()->make()->getAttributes();
        $attributes['plant_id'] = $id;
        try {
            DB::table('maintenance_services')->insert($attributes);
            $this->fail('Invalid FK accepted.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
        $this->assertDatabaseCount('maintenance_services', 0);
    }

    public function test_database_restricts_deleting_plant_with_services(): void
    {
        $service = MaintenanceService::factory()->create();
        try {
            $service->plant->delete();
            $this->fail('Referenced plant deleted.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0]);
        }
        $this->assertModelExists($service);
        $this->assertModelExists($service->plant);
    }

    public function test_model_refuses_physical_deletion(): void
    {
        $service = MaintenanceService::factory()->create();
        try {
            $service->delete();
            $this->fail('Service deleted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service', $exception->errors());
        }
        $this->assertModelExists($service);
    }

    public function test_database_supplies_planned_default_when_status_is_omitted(): void
    {
        $attributes = MaintenanceService::factory()->make()->getAttributes();
        unset($attributes['status']);
        $id = DB::table('maintenance_services')->insertGetId($attributes);

        $this->assertSame('planned', DB::table('maintenance_services')->where('id', $id)->value('status'));
    }
}
