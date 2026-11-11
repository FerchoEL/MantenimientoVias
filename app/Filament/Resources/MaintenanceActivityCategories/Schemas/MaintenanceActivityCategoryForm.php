<?php

namespace App\Filament\Resources\MaintenanceActivityCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaintenanceActivityCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100)
                    ->unique()
                    ->validationMessages([
                        'required' => 'El nombre de la categoría es obligatorio.',
                        'max' => 'El nombre de la categoría no puede superar los 100 caracteres.',
                        'unique' => 'Ya existe una categoría con ese nombre.',
                    ]),
                Textarea::make('description')->label('Descripción')->nullable(),
                TextInput::make('sort_order')
                    ->label('Orden de presentación')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }
}
