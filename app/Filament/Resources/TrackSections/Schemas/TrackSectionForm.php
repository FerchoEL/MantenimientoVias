<?php

namespace App\Filament\Resources\TrackSections\Schemas;

use App\Models\Track;
use App\Models\TrackSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class TrackSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('track_id')
                    ->label('Vía propietaria')
                    ->relationship(
                        'track',
                        'name',
                        function (Builder $query, Select $component): Builder {
                            $query->with('plant.client')->where(function (Builder $query) use ($component): void {
                                $query->where('active', true)
                                    ->whereHas('plant', fn (Builder $plantQuery): Builder => $plantQuery
                                        ->where('active', true)
                                        ->whereHas('client', fn (Builder $clientQuery): Builder => $clientQuery->where('active', true)));

                                $record = $component->getRecord();

                                if ($record instanceof TrackSection && $record->track_id !== null) {
                                    $query->orWhereKey($record->track_id);
                                }
                            });

                            return $query;
                        },
                    )
                    ->getOptionLabelFromRecordUsing(fn (Track $record): string => "{$record->plant->client->name} — {$record->plant->name} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?TrackSection $record): bool => $record !== null)
                    ->dehydrated()
                    ->validationMessages(['required' => 'La vía propietaria es obligatoria.'])
                    ->helperText(fn (?TrackSection $record): ?string => $record !== null && (! $record->track->active || ! $record->track->plant->active || ! $record->track->plant->client->active)
                        ? 'Este tramo pertenece a infraestructura inactiva. Se conserva para consulta histórica y su vía no puede reasignarse.'
                        : null),
                TextInput::make('name')
                    ->label('Nombre del tramo')
                    ->required()
                    ->maxLength(120)
                    ->unique(modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('track_id', $get('track_id')))
                    ->validationMessages([
                        'required' => 'El nombre del tramo es obligatorio.',
                        'max' => 'El nombre del tramo no puede superar los 120 caracteres.',
                        'unique' => 'Ya existe un tramo con ese nombre para la vía seleccionada.',
                    ]),
                TextInput::make('code')
                    ->label('Código del tramo')
                    ->maxLength(50)
                    ->nullable()
                    ->unique(modifyRuleUsing: function (Unique $rule, Get $get, ?TrackSection $record): Unique {
                        return $rule->where('track_id', $record?->track_id ?? $get('track_id'));
                    })
                    ->validationMessages([
                        'max' => 'El código del tramo no puede superar los 50 caracteres.',
                        'unique' => 'Ya existe un tramo con ese código para la vía seleccionada.',
                    ]),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }
}
