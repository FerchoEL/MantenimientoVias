<?php

namespace App\Models;

use Database\Factories\TrackSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['track_id', 'name', 'code', 'active'])]
class TrackSection extends Model
{
    /** @use HasFactory<TrackSectionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (TrackSection $trackSection): void {
            $track = Track::query()->with('plant.client')->find($trackSection->track_id);

            if ($track !== null
                && (! $track->active || ! $track->plant->active || ! $track->plant->client->active)) {
                throw ValidationException::withMessages([
                    'track_id' => 'No se pueden crear tramos para una vía, planta o cliente inactivo.',
                ]);
            }
        });

    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return BelongsTo<Track, $this> */
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }
}
