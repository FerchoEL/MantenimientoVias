<?php

namespace App\Filament\Resources\MaintenanceActivityCategories\Tables;

use App\Models\MaintenanceActivityCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceActivityCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('description')->label('Descripción')->limit(60),
                TextColumn::make('sort_order')->label('Orden')->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (MaintenanceActivityCategory $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (MaintenanceActivityCategory $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (MaintenanceActivityCategory $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (MaintenanceActivityCategory $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (MaintenanceActivityCategory $record): void {
                        $record->update(['active' => ! $record->active]);

                        Notification::make()
                            ->title($record->active ? 'Categoría activada' : 'Categoría desactivada')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
