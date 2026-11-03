<?php

namespace App\Filament\Resources\Tracks\RelationManagers;

use App\Filament\Resources\Tracks\Pages\EditTrack;
use App\Models\Track;
use App\Models\TrackSection;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof Track || $pageClass !== EditTrack::class) {
            return false;
        }

        $track = Track::query()->find($ownerRecord->getKey());

        return $track !== null && (auth()->user()?->can('view', $track) ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del tramo')
                    ->required()
                    ->maxLength(120)
                    ->unique(modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                        'track_id',
                        $this->getOwnerRecord()->getKey(),
                    ))
                    ->validationMessages([
                        'required' => 'El nombre del tramo es obligatorio.',
                        'max' => 'El nombre del tramo no puede superar los 120 caracteres.',
                        'unique' => 'Ya existe un tramo con ese nombre para esta vía.',
                    ]),
                TextInput::make('code')
                    ->label('Código')
                    ->maxLength(50)
                    ->nullable()
                    ->unique(modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                        'track_id',
                        $this->getOwnerRecord()->getKey(),
                    ))
                    ->validationMessages([
                        'max' => 'El código no puede superar los 50 caracteres.',
                        'unique' => 'Ya existe un tramo con ese código para esta vía.',
                    ]),
                Toggle::make('active')->label('Activo')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Tramos')
            ->modelLabel('tramo')
            ->pluralModelLabel('tramos')
            ->columns([
                TextColumn::make('name')->label('Nombre del tramo')->searchable()->sortable(),
                TextColumn::make('code')->label('Código')->searchable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->headerActions([
                CreateAction::make()->label('Registrar tramo'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                Action::make('toggleActive')
                    ->label(fn (TrackSection $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (TrackSection $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (TrackSection $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (TrackSection $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (TrackSection $record): void {
                        $record->update(['active' => ! $record->active]);

                        Notification::make()
                            ->title($record->active ? 'Tramo activado' : 'Tramo desactivado')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
