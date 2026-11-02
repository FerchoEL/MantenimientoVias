<?php

namespace App\Filament\Resources\TrackSwitches\Schemas;

use App\Models\Plant;
use App\Models\TrackSwitch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class TrackSwitchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plant_id')
                    ->label('Planta')
                    ->relationship(
                        'plant',
                        'name',
                        function (Builder $query, Select $component): Builder {
                            $query->with('client')->where(function (Builder $query) use ($component): void {
                                $query->where('active', true)
                                    ->whereHas('client', fn (Builder $clientQuery): Builder => $clientQuery->where('active', true));

                                $record = $component->getRecord();

                                if ($record instanceof TrackSwitch && $record->plant_id !== null) {
                                    $query->orWhereKey($record->plant_id);
                                }
                            });

                            return $query;
                        },
                    )
                    ->getOptionLabelFromRecordUsing(fn (Plant $record): string => "{$record->client->name} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages(['required' => 'La planta es obligatoria.'])
                    ->helperText(fn (?TrackSwitch $record): ?string => $record !== null && (! $record->plant->active || ! $record->plant->client->active)
                        ? 'Este cambio de vía pertenece a una planta o cliente inactivo. La información se conserva para consulta histórica.'
                        : null),
                TextInput::make('code')
                    ->label('Código del cambio')
                    ->required()
                    ->maxLength(50)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('plant_id', $get('plant_id')))
                    ->validationMessages([
                        'required' => 'El código del cambio es obligatorio.',
                        'max' => 'El código no puede superar los 50 caracteres.',
                        'unique' => 'Ya existe un cambio con ese código para la planta seleccionada.',
                    ]),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(120)
                    ->validationMessages([
                        'required' => 'El nombre es obligatorio.',
                        'max' => 'El nombre no puede superar los 120 caracteres.',
                    ]),
                Textarea::make('description')->label('Descripción'),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }
}
