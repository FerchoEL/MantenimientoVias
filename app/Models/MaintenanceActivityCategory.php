<?php

namespace App\Models;

use Database\Factories\MaintenanceActivityCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'description', 'active', 'sort_order'])]
class MaintenanceActivityCategory extends Model
{
    /** @use HasFactory<MaintenanceActivityCategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (MaintenanceActivityCategory $category): void {
            $category->name = trim((string) $category->name);
            $category->description = $category->description === null ? null : trim((string) $category->description);

            Validator::make($category->getAttributes(), [
                'name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string'],
                'active' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            ], [
                'name.required' => 'El nombre de la categoría es obligatorio.',
                'name.max' => 'El nombre de la categoría no puede superar los 100 caracteres.',
                'sort_order.min' => 'El orden de presentación no puede ser negativo.',
            ])->validate();
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'category' => 'No se pueden eliminar categorías; se debe conservar su histórico.',
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    #[Scope]
    protected function activeOrdered(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('name');
    }
}
