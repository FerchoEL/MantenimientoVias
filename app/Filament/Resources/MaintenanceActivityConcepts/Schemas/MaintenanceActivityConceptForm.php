<?php

namespace App\Filament\Resources\MaintenanceActivityConcepts\Schemas;

use App\Models\MaintenanceActivityConcept;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class MaintenanceActivityConceptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('maintenance_activity_category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name', function (Builder $query, Select $component): Builder {
                        $query->where('active', true);
                        $record = $component->getRecord();
                        if ($record instanceof MaintenanceActivityConcept && $record->maintenance_activity_category_id !== null) {
                            $query->orWhereKey($record->maintenance_activity_category_id);
                        }

                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')->label('Nombre')->required()->maxLength(160)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('maintenance_activity_category_id', $get('maintenance_activity_category_id'))),
                Textarea::make('description')->label('Descripción')->nullable(),
                TextInput::make('sort_order')->label('Orden')->numeric()->integer()->minValue(0)->default(0)->required(),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }
}
