<?php

namespace App\Filament\Resources\Tracks\Tables;

use App\Models\Client;
use App\Models\Track;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TracksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Vía')->searchable()->sortable(),
                TextColumn::make('code')->label('Código')->searchable(),
                TextColumn::make('plant.client.name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('plant.name')->label('Planta')->searchable()->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('client')
                    ->label('Cliente')
                    ->options(fn (): array => Client::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('plant', fn (Builder $plantQuery): Builder => $plantQuery->where('client_id', $data['value']))
                        : $query),
                SelectFilter::make('plant')->label('Planta')->relationship('plant', 'name')->searchable()->preload(),
                SelectFilter::make('active')->label('Estado')->options([true => 'Activa', false => 'Inactiva']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Track $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (Track $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (Track $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (Track $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (Track $record): void {
                        $record->update(['active' => ! $record->active]);

                        Notification::make()
                            ->title($record->active ? 'Vía activada' : 'Vía desactivada')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
