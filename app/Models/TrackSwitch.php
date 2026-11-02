<?php

namespace App\Models;

use Database\Factories\TrackSwitchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['plant_id', 'code', 'name', 'description', 'active'])]
class TrackSwitch extends Model
{
    /** @use HasFactory<TrackSwitchFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (TrackSwitch $trackSwitch): void {
            if ($trackSwitch->plant()->where(function (Builder $query): void {
                $query->where('active', false)
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('active', false));
            })->exists()) {
                throw ValidationException::withMessages([
                    'plant_id' => 'No se pueden crear cambios para una planta o cliente inactivo.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return BelongsTo<Plant, $this> */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
