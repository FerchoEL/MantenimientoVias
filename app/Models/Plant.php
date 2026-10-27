<?php

namespace App\Models;

use Database\Factories\PlantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['client_id', 'name', 'short_name', 'active', 'observations'])]
class Plant extends Model
{
    /** @use HasFactory<PlantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Plant $plant): void {
            if ($plant->client()->where('active', false)->exists()) {
                throw ValidationException::withMessages([
                    'client_id' => 'No se pueden crear plantas para un cliente inactivo.',
                ]);
            }
        });

        static::updating(function (Plant $plant): void {
            if (! $plant->isDirty('client_id')) {
                return;
            }

            if ($plant->client()->where('active', false)->exists()) {
                throw ValidationException::withMessages([
                    'client_id' => 'No se puede asignar una planta a un cliente inactivo.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
