<?php

namespace App\Models;

use App\MaintenancePeriodStatus;
use Database\Factories\MaintenancePeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

#[Fillable(['maintenance_service_id', 'start_date', 'end_date', 'status', 'created_by', 'submitted_at', 'approved_at', 'closed_at'])]
class MaintenancePeriod extends Model
{
    /** @use HasFactory<MaintenancePeriodFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (MaintenancePeriod $period): void {
            Validator::make($period->getAttributes(), [
                'maintenance_service_id' => ['required', 'integer', 'exists:maintenance_services,id'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'status' => ['required', Rule::enum(MaintenancePeriodStatus::class)],
                'created_by' => ['required', 'integer', 'exists:users,id'],
                'submitted_at' => ['nullable', 'date'],
                'approved_at' => ['nullable', 'date'],
                'closed_at' => ['nullable', 'date'],
            ], [
                'maintenance_service_id.required' => 'El Servicio de mantenimiento es obligatorio.',
                'maintenance_service_id.exists' => 'El Servicio de mantenimiento seleccionado no existe.',
                'start_date.required' => 'La fecha inicial del periodo es obligatoria.',
                'end_date.required' => 'La fecha final del periodo es obligatoria.',
                'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
                'status.required' => 'El estado del periodo es obligatorio.',
                'created_by.required' => 'El creador del periodo es obligatorio.',
                'created_by.exists' => 'El creador del periodo no existe.',
            ])->validate();
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'period' => 'No se pueden eliminar periodos; se debe conservar su histórico.',
            ]);
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MaintenanceService::class, 'maintenance_service_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => MaintenancePeriodStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
