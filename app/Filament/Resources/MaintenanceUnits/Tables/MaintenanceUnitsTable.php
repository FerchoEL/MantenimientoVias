<?php

namespace App\Filament\Resources\MaintenanceUnits\Tables;

use App\Models\MaintenanceUnit;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('symbol')->label('Símbolo')->searchable()->sortable(),
                TextColumn::make('sort_order')->label('Orden')->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (MaintenanceUnit $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (MaintenanceUnit $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (MaintenanceUnit $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (MaintenanceUnit $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (MaintenanceUnit $record): void {
                        $record->update(['active' => ! $record->active]);
                        Notification::make()->title($record->active ? 'Unidad activada' : 'Unidad desactivada')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
