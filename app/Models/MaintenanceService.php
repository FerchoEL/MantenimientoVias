<?php

namespace App\Models;

use App\MaintenanceServiceStatus;
use App\ReportFrequency;
use Database\Factories\MaintenanceServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

#[Fillable(['plant_id', 'name', 'order_number', 'start_date', 'end_date', 'report_frequency', 'status', 'notes'])]
class MaintenanceService extends Model
{
    /** @use HasFactory<MaintenanceServiceFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'planned'];

    protected static function booted(): void
    {
        static::saving(function (MaintenanceService $service): void {
            Validator::make($service->getAttributes(), [
                'name' => ['required', 'string', 'max:150'],
                'order_number' => ['nullable', 'string', 'max:80'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'report_frequency' => ['required', Rule::enum(ReportFrequency::class)],
                'status' => ['required', Rule::enum(MaintenanceServiceStatus::class)],
                'notes' => ['nullable', 'string'],
            ], [
                'name.required' => 'El nombre del servicio es obligatorio.',
                'name.max' => 'El nombre del servicio no puede superar 150 caracteres.',
                'order_number.max' => 'El pedido o contrato no puede superar 80 caracteres.',
                'start_date.required' => 'La fecha inicial es obligatoria.',
                'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ])->validate();
        });

        static::creating(function (MaintenanceService $service): void {
            if ($service->plant()->where(function (Builder $query): void {
                $query->where('active', false)
                    ->orWhereHas('client', fn (Builder $clientQuery): Builder => $clientQuery->where('active', false));
            })->exists()) {
                throw ValidationException::withMessages([
                    'plant_id' => 'No se pueden crear servicios para una planta o cliente inactivo.',
                ]);
            }
        });

        static::deleting(function (MaintenanceService $service): void {
            throw ValidationException::withMessages([
                'service' => 'No se pueden eliminar servicios de mantenimiento; se debe conservar su histórico.',
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'report_frequency' => ReportFrequency::class,
            'status' => MaintenanceServiceStatus::class,
        ];
    }

    /** @return BelongsTo<Plant, $this> */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
