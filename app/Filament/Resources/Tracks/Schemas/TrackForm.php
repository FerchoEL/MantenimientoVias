<?php

namespace App\Filament\Resources\Tracks\Schemas;

use App\Models\Plant;
use App\Models\Track;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class TrackForm
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

                                if ($record instanceof Track && $record->plant_id !== null) {
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
                    ->helperText(fn (?Track $record): ?string => $record !== null && (! $record->plant->active || ! $record->plant->client->active)
                        ? 'Esta vía pertenece a una planta o cliente inactivo. La información se conserva para consulta histórica.'
                        : null),
                TextInput::make('name')
                    ->label('Nombre de vía')
                    ->required()
                    ->maxLength(120)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('plant_id', $get('plant_id')))
                    ->validationMessages([
                        'required' => 'El nombre de vía es obligatorio.',
                        'max' => 'El nombre de vía no puede superar los 120 caracteres.',
                        'unique' => 'Ya existe una vía con ese nombre para la planta seleccionada.',
                    ]),
                TextInput::make('code')
                    ->label('Código')
                    ->maxLength(50)
                    ->validationMessages(['max' => 'El código no puede superar los 50 caracteres.']),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }
}
