<?php

namespace App\Filament\Resources\MaintenanceUnits\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaintenanceUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(100)->unique(),
                TextInput::make('symbol')->label('Símbolo')->required()->maxLength(20)->unique(),
                Textarea::make('description')->label('Descripción')->nullable(),
                TextInput::make('sort_order')->label('Orden')->numeric()->integer()->minValue(0)->default(0)->required(),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }
}
