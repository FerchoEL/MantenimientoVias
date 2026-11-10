<?php

namespace App\Models;

use Database\Factories\MaintenanceServiceSupervisorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Fillable(['maintenance_service_id', 'user_id', 'start_date', 'end_date', 'active'])]
class MaintenanceServiceSupervisor extends Model
{
    /** @use HasFactory<MaintenanceServiceSupervisorFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['active' => true];

    protected static function booted(): void
    {
        static::saving(function (MaintenanceServiceSupervisor $assignment): void {
            Validator::make($assignment->getAttributes(), [
                'maintenance_service_id' => ['required', 'integer', 'exists:maintenance_services,id'],
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'active' => ['required', 'boolean'],
            ], [
                'maintenance_service_id.required' => 'El Servicio de mantenimiento es obligatorio.',
                'maintenance_service_id.exists' => 'El Servicio de mantenimiento seleccionado no existe.',
                'user_id.required' => 'El Supervisor es obligatorio.',
                'user_id.exists' => 'El usuario seleccionado no existe.',
                'start_date.required' => 'La fecha inicial de la asignación es obligatoria.',
                'end_date.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ])->validate();
        });

        static::creating(function (MaintenanceServiceSupervisor $assignment): void {
            $assignment->ensureSupervisorRole();
        });

        static::updating(function (MaintenanceServiceSupervisor $assignment): void {
            if ($assignment->isDirty('user_id')) {
                $assignment->ensureSupervisorRole();
            }
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'assignment' => 'No se pueden eliminar asignaciones; se debe conservar su histórico.',
            ]);
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MaintenanceService::class, 'maintenance_service_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'boolean',
        ];
    }

    private function ensureSupervisorRole(): void
    {
        $hasRole = $this->supervisor()
            ->whereHas('roles', function (Builder $query): void {
                $query->where('name', 'Supervisor')
                    ->where('guard_name', 'web');
            })
            ->exists();

        if (! $hasRole) {
            throw ValidationException::withMessages([
                'user_id' => 'El usuario seleccionado debe tener el rol Supervisor en el guard web.',
            ]);
        }
    }
}
