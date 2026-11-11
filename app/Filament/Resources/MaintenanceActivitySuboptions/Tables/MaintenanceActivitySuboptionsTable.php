<?php

namespace App\Filament\Resources\MaintenanceActivitySuboptions\Tables;

use App\Models\MaintenanceActivitySuboption;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceActivitySuboptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('concept.category.name')->label('Categoría')->searchable()->sortable(),
                TextColumn::make('concept.name')->label('Concepto')->searchable()->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('sort_order')->label('Orden')->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('maintenance_activity_concept_id')->label('Concepto')->relationship('concept', 'name')->searchable()->preload(),
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (MaintenanceActivitySuboption $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (MaintenanceActivitySuboption $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (MaintenanceActivitySuboption $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (MaintenanceActivitySuboption $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (MaintenanceActivitySuboption $record): void {
                        $record->update(['active' => ! $record->active]);
                        Notification::make()->title($record->active ? 'Subopción activada' : 'Subopción desactivada')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
