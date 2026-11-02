<?php

namespace App\Filament\Resources\TrackSwitches\Tables;

use App\Models\TrackSwitch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrackSwitchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Código')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('plant.client.name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('plant.name')->label('Planta')->searchable()->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('plant')->label('Planta')->relationship('plant', 'name')->searchable()->preload(),
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (TrackSwitch $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (TrackSwitch $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (TrackSwitch $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (TrackSwitch $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (TrackSwitch $record): void {
                        $record->update(['active' => ! $record->active]);

                        Notification::make()
                            ->title($record->active ? 'Cambio de vía activado' : 'Cambio de vía desactivado')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
