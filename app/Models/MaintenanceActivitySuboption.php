<?php

namespace App\Models;

use Database\Factories\MaintenanceActivitySuboptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Fillable(['maintenance_activity_concept_id', 'name', 'description', 'active', 'sort_order'])]
class MaintenanceActivitySuboption extends Model
{
    /** @use HasFactory<MaintenanceActivitySuboptionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (MaintenanceActivitySuboption $suboption): void {
            $suboption->name = trim((string) $suboption->name);
            $suboption->description = $suboption->description === null ? null : trim((string) $suboption->description);

            Validator::make($suboption->getAttributes(), [
                'maintenance_activity_concept_id' => ['required', 'integer', 'exists:maintenance_activity_concepts,id'],
                'name' => ['required', 'string', 'max:160'],
                'description' => ['nullable', 'string'],
                'active' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            ])->validate();
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'suboption' => 'No se pueden eliminar subopciones; se debe conservar su histórico.',
            ]);
        });
    }

    /** @return BelongsTo<MaintenanceActivityConcept, $this> */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(MaintenanceActivityConcept::class, 'maintenance_activity_concept_id');
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
