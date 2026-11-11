<?php

namespace App\Models;

use Database\Factories\MaintenanceActivityConceptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Fillable(['maintenance_activity_category_id', 'name', 'description', 'active', 'sort_order'])]
class MaintenanceActivityConcept extends Model
{
    /** @use HasFactory<MaintenanceActivityConceptFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (MaintenanceActivityConcept $concept): void {
            $concept->name = trim((string) $concept->name);
            $concept->description = $concept->description === null ? null : trim((string) $concept->description);

            Validator::make($concept->getAttributes(), [
                'maintenance_activity_category_id' => ['required', 'integer', 'exists:maintenance_activity_categories,id'],
                'name' => ['required', 'string', 'max:160'],
                'description' => ['nullable', 'string'],
                'active' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            ])->validate();
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'concept' => 'No se pueden eliminar conceptos; se debe conservar su histórico.',
            ]);
        });
    }

    /** @return BelongsTo<MaintenanceActivityCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MaintenanceActivityCategory::class, 'maintenance_activity_category_id');
    }

    /** @return HasMany<MaintenanceActivitySuboption, $this> */
    public function suboptions(): HasMany
    {
        return $this->hasMany(MaintenanceActivitySuboption::class);
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
