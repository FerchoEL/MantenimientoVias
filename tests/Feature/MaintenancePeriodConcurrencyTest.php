<?php

namespace Tests\Feature;

use App\MaintenancePeriodLifecycle;
use App\MaintenancePeriodStatus;
use App\MaintenanceServiceStatus;
use App\Models\MaintenancePeriod;
use App\Models\MaintenanceService;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\MariaDbTestCase;

class MaintenancePeriodConcurrencyTest extends MariaDbTestCase
{
    /** @var list<Process> */
    private array $workers = [];

    protected function tearDown(): void
    {
        if ($this->app && config('database.default') === 'mv030_test') {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        foreach ($this->workers as $worker) {
            $worker->stop();
        }
        parent::tearDown();
    }

    /** @return array{User, MaintenanceService} */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);

        return [User::factory()->create()->assignRole('Administrador'), MaintenanceService::factory()->create(['status' => MaintenanceServiceStatus::Active])];
    }

    public static function conflicts(): array
    {
        return ['duplicate' => ['2026-11-05', '2026-11-11'], 'overlap' => ['2026-11-10', '2026-11-20']];
    }

    #[DataProvider('conflicts')]
    public function test_waiting_open_rechecks_committed_period_and_rejects_conflict(string $start, string $end): void
    {
        [$actor, $service] = $this->scenario();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        MaintenanceService::query()->lockForUpdate()->findOrFail($service->id);
        $worker = $this->worker('open', $actor, $service->id, $start, $end);
        $this->assertWaiting($worker);
        $period = app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, ['start_date' => '2026-11-05', 'end_date' => '2026-11-11']);
        DB::commit();
        $this->assertResult($worker, 'validation');
        $this->assertSame([$period->id], $service->periods()->pluck('id')->all());
    }

    public function test_waiting_open_succeeds_after_competing_transaction_rolls_back(): void
    {
        [$actor, $service] = $this->scenario();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, ['start_date' => '2026-11-05', 'end_date' => '2026-11-11']);
        $worker = $this->worker('open', $actor, $service->id);
        $this->assertWaiting($worker);
        DB::rollBack();
        $this->assertResult($worker, 'ok');
        $this->assertSame(1, $service->periods()->count());
    }

    public function test_waiting_open_rechecks_service_status_after_lock_is_released(): void
    {
        [$actor, $service] = $this->scenario();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        $service->refreshForUpdate();
        $worker = $this->worker('open', $actor, $service->id);
        $this->assertWaiting($worker);
        $service->transitionTo(MaintenanceServiceStatus::Completed);
        DB::commit();
        $this->assertResult($worker, 'validation');
        $this->assertSame(0, $service->periods()->count());
    }

    public function test_two_closures_preserve_first_closure_timestamp(): void
    {
        [$actor, $service] = $this->scenario();
        $period = MaintenancePeriod::factory()->for($service, 'service')->create(['status' => MaintenancePeriodStatus::Approved]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        $closed = app(MaintenancePeriodLifecycle::class)->close($actor, $period->id);
        $worker = $this->worker('close', $actor, $period->id);
        $this->assertWaiting($worker);
        DB::commit();
        $this->assertResult($worker, 'validation');
        $this->assertSame(MaintenancePeriodStatus::Closed, $period->fresh()->status);
        $this->assertSame($closed->closed_at->toDateTimeString(), $period->fresh()->closed_at->toDateTimeString());
    }

    public function test_stale_model_update_waits_and_cannot_overwrite_concurrent_closure(): void
    {
        [$actor, $service] = $this->scenario();
        $period = MaintenancePeriod::factory()->for($service, 'service')->create(['status' => MaintenancePeriodStatus::Approved]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        app(MaintenancePeriodLifecycle::class)->close($actor, $period->id);
        $worker = $this->worker('edit', $actor, $period->id);
        $this->assertWaiting($worker);
        DB::commit();
        $this->assertResult($worker, 'validation');
        $this->assertSame('2026-11-11', $period->fresh()->end_date->toDateString());
        $this->assertSame(MaintenancePeriodStatus::Closed, $period->fresh()->status);
    }

    public function test_another_service_in_same_plant_can_open_before_first_transaction_commits(): void
    {
        [$actor, $service] = $this->scenario();
        $other = MaintenanceService::factory()->for($service->plant)->create(['status' => MaintenanceServiceStatus::Active]);
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        DB::beginTransaction();
        app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, ['start_date' => '2026-11-05', 'end_date' => '2026-11-11']);
        $worker = $this->worker('open', $actor, $other->id);
        $this->assertResult($worker, 'ok');
        DB::commit();
        $this->assertSame(1, $service->periods()->count());
        $this->assertSame(1, $other->periods()->count());
    }

    public function test_existing_repeatable_read_snapshot_is_rejected_before_writing(): void
    {
        [$actor, $service] = $this->scenario();
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        $service->periods()->count();
        try {
            app(MaintenancePeriodLifecycle::class)->open($actor, $service->id, ['start_date' => '2026-11-05', 'end_date' => '2026-11-11']);
            $this->fail('An existing repeatable-read snapshot was accepted.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('READ COMMITTED', $exception->getMessage());
        }
        DB::rollBack();
        $this->assertSame(0, $service->periods()->count());
    }

    private function worker(string $operation, User $actor, int $id, string $start = '2026-11-05', string $end = '2026-11-11'): Process
    {
        $code = <<<'PHPWORKER'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\MariaDbTestCase::configureIsolatedDatabase($argv[1]);
$connection = Illuminate\Support\Facades\DB::selectOne('SELECT CONNECTION_ID() AS id');
echo 'ready:'.$connection->id."\n";
try {
    $actor = App\Models\User::findOrFail((int) $argv[3]);
    if ($argv[2] === 'edit') {
        App\Models\MaintenancePeriod::findOrFail((int) $argv[4])->update(['end_date' => '2026-12-01']);
    } else {
        app(App\MaintenancePeriodLifecycle::class)->{$argv[2]}($actor, (int) $argv[4], ['start_date' => $argv[5], 'end_date' => $argv[6]]);
    }
    echo "result:ok\n";
} catch (Illuminate\Validation\ValidationException $exception) {
    echo "result:validation\n";
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage());
    exit(1);
}
PHPWORKER;
        $worker = new Process([PHP_BINARY, '-r', $code, getenv('MV030_MARIADB_SOCKET'), $operation, (string) $actor->id, (string) $id, $start, $end], base_path(), [
            'APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '',
        ], timeout: 15);
        $this->workers[] = $worker;
        $worker->start();

        return $worker;
    }

    private function assertWaiting(Process $worker): void
    {
        $deadline = microtime(true) + 8;
        do {
            if (preg_match('/ready:(\d+)/', $worker->getOutput(), $matches)) {
                $waiting = DB::selectOne('SELECT COUNT(*) AS total FROM information_schema.INNODB_LOCK_WAITS w JOIN information_schema.INNODB_TRX t ON t.trx_id = w.requesting_trx_id WHERE t.trx_mysql_thread_id = ?', [(int) $matches[1]]);
                if ((int) $waiting->total > 0) {
                    $this->assertTrue($worker->isRunning());

                    return;
                }
            }
            usleep(200000);
        } while ($worker->isRunning() && microtime(true) < $deadline);
        $this->fail('Worker did not enter an observed InnoDB lock wait: '.$worker->getOutput().$worker->getErrorOutput().' '.json_encode(DB::select('SHOW ENGINE INNODB STATUS')));
    }

    private function assertResult(Process $worker, string $expected): void
    {
        $worker->wait();
        $this->assertSame(0, $worker->getExitCode(), $worker->getErrorOutput());
        $this->assertStringContainsString('result:'.$expected, $worker->getOutput());
    }
}
