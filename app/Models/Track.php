<?php

namespace App\Models;

use Database\Factories\TrackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable(['plant_id', 'name', 'code', 'sketch_disk', 'sketch_path', 'active'])]
class Track extends Model
{
    /** @use HasFactory<TrackFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Track $track): void {
            if ($track->plant()->where(function (Builder $query): void {
                $query->where('active', false)
                    ->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->where('active', false));
            })->exists()) {
                throw ValidationException::withMessages([
                    'plant_id' => 'No se pueden crear vías para una planta o cliente inactivo.',
                ]);
            }
        });

        static::updating(function (Track $track): void {
            if (! $track->isDirty('plant_id')) {
                return;
            }

            if ($track->plant()->where(function (Builder $query): void {
                $query->where('active', false)
                    ->orWhereHas('client', fn (Builder $clientQuery): Builder => $clientQuery->where('active', false));
            })->exists()) {
                throw ValidationException::withMessages([
                    'plant_id' => 'No se puede asignar una vía a una planta o cliente inactivo.',
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

    /** @return HasMany<TrackSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(TrackSection::class);
    }
}
