<?php

namespace App;

use App\Models\MaintenancePeriod;
use App\Models\MaintenanceService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

class MaintenancePeriodLifecycle
{
    /** @param array<string, mixed> $dates */
    public function open(User $actor, int $serviceId, array $dates): MaintenancePeriod
    {
        return $this->transaction(function () use ($actor, $serviceId, $dates): MaintenancePeriod {
            // Lock the parent even when it has no periods; independent services remain independent.
            $service = MaintenanceService::query()->lockForUpdate()->findOrFail($serviceId);
            Gate::forUser(User::query()->findOrFail($actor->getKey()))->authorize('create', [MaintenancePeriod::class, $service]);

            $plant = $service->plant()->sharedLock()->firstOrFail();
            $client = $plant->client()->sharedLock()->firstOrFail();
            if ($service->status !== MaintenanceServiceStatus::Active || ! $client->active) {
                throw ValidationException::withMessages(['service' => 'La apertura requiere un Servicio activo y un Cliente activo.']);
            }

            $validated = Validator::make($dates, [
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ])->validate();
            $start = CarbonImmutable::parse($validated['start_date']);
            $end = CarbonImmutable::parse($validated['end_date']);
            $this->validateDates($service, $start, $end);

            return $service->periods()->create([
                'start_date' => $start,
                'end_date' => $end,
                'status' => MaintenancePeriodStatus::Open,
                'created_by' => $actor->getKey(),
            ]);
        });
    }

    public function close(User $actor, int $periodId): MaintenancePeriod
    {
        $serviceId = MaintenancePeriod::query()->findOrFail($periodId)->maintenance_service_id;

        return $this->transaction(function () use ($actor, $periodId, $serviceId): MaintenancePeriod {
            $service = MaintenanceService::query()->lockForUpdate()->findOrFail($serviceId);
            $period = $service->periods()->lockForUpdate()->findOrFail($periodId);
            Gate::forUser(User::query()->findOrFail($actor->getKey()))->authorize('close', [$period, $service]);

            if ($period->status !== MaintenancePeriodStatus::Approved || $period->closed_at !== null) {
                throw ValidationException::withMessages(['status' => 'Solo se puede cerrar un periodo aprobado que no haya sido cerrado.']);
            }

            $period->status = MaintenancePeriodStatus::Closed;
            $period->closed_at = now(config('app.timezone'));
            $period->save();

            return $period;
        });
    }

    /** A caller-owned repeatable-read snapshot may predate acquisition of the service lock. */
    private function transaction(Closure $operation): MaintenancePeriod
    {
        $connection = DB::connection();
        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) && $connection->transactionLevel() > 0
            && strtoupper($connection->selectOne('SELECT @@tx_isolation AS isolation_level')->isolation_level) !== 'READ-COMMITTED') {
            throw new LogicException('Invoke period operations outside an existing MariaDB transaction, or use READ COMMITTED for the outer transaction.');
        }

        return $connection->transaction($operation);
    }

    private function validateDates(MaintenanceService $service, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (($service->start_date !== null && $start->lt($service->start_date))
            || ($service->end_date !== null && $end->gt($service->end_date))) {
            throw ValidationException::withMessages(['dates' => 'El periodo debe estar dentro de la vigencia del Servicio; el último corte parcial no está habilitado.']);
        }

        if ($service->periods()->whereDate('start_date', '<=', $end->toDateString())->whereDate('end_date', '>=', $start->toDateString())->exists()) {
            throw ValidationException::withMessages(['dates' => 'El periodo se solapa con un periodo existente del Servicio.']);
        }

        if ($service->report_frequency !== ReportFrequency::Biweekly) {
            return;
        }

        if (! $end->equalTo($start->addDays(14))) {
            throw ValidationException::withMessages(['end_date' => 'La quincena debe tener exactamente 15 días naturales inclusivos.']);
        }

        $previous = $service->periods()->orderByDesc('end_date')->first();
        $expected = $previous?->end_date->copy()->addDay() ?? $service->start_date;
        if ($expected === null || ! $start->equalTo($expected)
            || $service->start_date === null || ((int) $service->start_date->diffInDays($start)) % 15 !== 0) {
            throw ValidationException::withMessages(['start_date' => 'La quincena debe continuar la secuencia anclada al inicio del Servicio, sin huecos.']);
        }
    }
}
