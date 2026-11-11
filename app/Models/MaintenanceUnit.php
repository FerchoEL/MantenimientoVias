<?php

namespace App\Models;

use Database\Factories\MaintenanceUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'symbol', 'description', 'active', 'sort_order'])]
class MaintenanceUnit extends Model
{
    /** @use HasFactory<MaintenanceUnitFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (MaintenanceUnit $unit): void {
            $unit->name = trim((string) $unit->name);
            $unit->symbol = trim((string) $unit->symbol);
            $unit->description = $unit->description === null ? null : trim((string) $unit->description);

            Validator::make($unit->getAttributes(), [
                'name' => ['required', 'string', 'max:100'],
                'symbol' => ['required', 'string', 'max:20'],
                'description' => ['nullable', 'string'],
                'active' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            ])->validate();
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'unit' => 'No se pueden eliminar unidades; se debe conservar su histórico.',
            ]);
        });
    }

    #[Scope]
    protected function activeOrdered(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }
}
