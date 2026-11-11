<?php

namespace App\Filament\Resources\MaintenanceActivityConcepts\Tables;

use App\Models\MaintenanceActivityConcept;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceActivityConceptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')->label('Categoría')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('sort_order')->label('Orden')->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('maintenance_activity_category_id')->label('Categoría')->relationship('category', 'name')->searchable()->preload(),
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (MaintenanceActivityConcept $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (MaintenanceActivityConcept $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (MaintenanceActivityConcept $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (MaintenanceActivityConcept $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (MaintenanceActivityConcept $record): void {
                        $record->update(['active' => ! $record->active]);
                        Notification::make()->title($record->active ? 'Concepto activado' : 'Concepto desactivado')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
