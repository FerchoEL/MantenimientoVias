<?php

namespace Tests\Feature\Models;

use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceSupervisor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class MaintenanceServiceSupervisorTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_assignment_has_typed_relations_and_casts(): void
    {
        $service = MaintenanceService::factory()->create();
        $supervisor = $this->supervisor();
        $assignment = $this->assignment($service, $supervisor, ['end_date' => '2026-11-19']);

        $this->assertInstanceOf(MaintenanceService::class, $assignment->service);
        $this->assertInstanceOf(User::class, $assignment->supervisor);
        $this->assertInstanceOf(Carbon::class, $assignment->start_date);
        $this->assertInstanceOf(Carbon::class, $assignment->end_date);
        $this->assertTrue($assignment->active);
        $this->assertNotNull($assignment->created_at);
        $this->assertNotNull($assignment->updated_at);
        $this->assertTrue($service->supervisorAssignments->contains($assignment));
        $this->assertTrue($supervisor->maintenanceServiceAssignments->contains($assignment));
    }

    public function test_factory_can_use_a_prepared_supervisor(): void
    {
        $supervisor = $this->supervisor();
        $assignment = MaintenanceServiceSupervisor::factory()->forSupervisor($supervisor)->create();

        $this->assertSame($supervisor->id, $assignment->user_id);
        $this->assertDatabaseHas('maintenance_service_supervisors', ['id' => $assignment->id]);
    }

    public function test_multiple_services_plants_and_overlapping_assignments_are_allowed(): void
    {
        $supervisor = $this->supervisor();
        $first = MaintenanceService::factory()->create();
        $second = MaintenanceService::factory()->create();

        $firstAssignment = $this->assignment($first, $supervisor, ['end_date' => '2026-11-19']);
        $secondAssignment = $this->assignment($second, $supervisor, [
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-24',
        ]);

        $this->assertNotSame($first->plant_id, $second->plant_id);
        $this->assertDatabaseCount('maintenance_service_supervisors', 2);
        $this->assertNotSame($firstAssignment->id, $secondAssignment->id);
    }

    public function test_repeated_history_for_the_same_pair_is_allowed(): void
    {
        $service = MaintenanceService::factory()->create();
        $supervisor = $this->supervisor();

        $this->assignment($service, $supervisor, ['end_date' => '2026-11-19']);
        $this->assignment($service, $supervisor, ['start_date' => '2026-11-19', 'end_date' => '2026-11-30']);

        $this->assertDatabaseCount('maintenance_service_supervisors', 2);
    }

    public function test_one_day_and_open_ended_periods_are_valid(): void
    {
        $service = MaintenanceService::factory()->create();
        $supervisor = $this->supervisor();

        $oneDay = $this->assignment($service, $supervisor, ['end_date' => '2026-11-05']);
        $open = $this->assignment($service, $supervisor, ['start_date' => '2026-11-06']);

        $this->assertSame('2026-11-05', $oneDay->end_date->toDateString());
        $this->assertNull($open->end_date);
    }

    public function test_active_defaults_true_and_deactivation_preserves_history(): void
    {
        $assignment = $this->assignment(MaintenanceService::factory()->create(), $this->supervisor());

        $this->assertTrue($assignment->active);
        $assignment->update(['active' => false]);
        $assignment->refresh();

        $this->assertFalse($assignment->active);
        $this->assertSame('2026-11-05', $assignment->start_date->toDateString());
    }

    public function test_missing_foreign_keys_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        MaintenanceServiceSupervisor::create([
            'maintenance_service_id' => 999999,
            'user_id' => 999999,
            'start_date' => '2026-11-05',
        ]);
    }

    public function test_non_supervisor_cannot_be_assigned(): void
    {
        $this->expectException(ValidationException::class);

        $this->assignment(MaintenanceService::factory()->create(), User::factory()->create());
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $this->expectException(ValidationException::class);

        $this->assignment(MaintenanceService::factory()->create(), $this->supervisor(), [
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-19',
        ]);
    }

    public function test_delete_restrictions_preserve_service_and_user_history(): void
    {
        $service = MaintenanceService::factory()->create();
        $supervisor = $this->supervisor();
        $this->assignment($service, $supervisor);

        $this->assertThrowsForeignKey(fn () => DB::table('maintenance_services')->where('id', $service->id)->delete());
        $this->assertThrowsForeignKey(fn () => $supervisor->forceDelete());
    }

    public function test_assignment_does_not_change_service_status(): void
    {
        $service = MaintenanceService::factory()->create();
        $this->assignment($service, $this->supervisor());

        $this->assertSame('planned', $service->fresh()->status->value);
    }

    public function test_assignment_deletion_is_blocked_to_preserve_history(): void
    {
        $assignment = $this->assignment(MaintenanceService::factory()->create(), $this->supervisor());

        $this->expectException(ValidationException::class);
        $assignment->delete();
    }

    private function supervisor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Supervisor');

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function assignment(MaintenanceService $service, User $supervisor, array $overrides = []): MaintenanceServiceSupervisor
    {
        return MaintenanceServiceSupervisor::create(array_merge([
            'maintenance_service_id' => $service->id,
            'user_id' => $supervisor->id,
            'start_date' => '2026-11-05',
            'end_date' => null,
        ], $overrides));
    }

    private function assertThrowsForeignKey(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a foreign key violation.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->errorInfo[0] ?? null);
        }
    }
}
