<?php

namespace App\Filament\Resources\TrackSections\Tables;

use App\Models\Plant;
use App\Models\Track;
use App\Models\TrackSection;
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

class TrackSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tramo')->searchable()->sortable(),
                TextColumn::make('code')->label('Código')->searchable(),
                TextColumn::make('track.name')->label('Vía')->searchable()->sortable(),
                TextColumn::make('track.plant.name')->label('Planta')->searchable()->sortable(),
                TextColumn::make('track.plant.client.name')->label('Cliente')->searchable()->sortable(),
                IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                SelectFilter::make('plant')
                    ->label('Planta')
                    ->options(fn (): array => Plant::query()->with('client')->orderBy('name')->get()->mapWithKeys(
                        fn (Plant $plant): array => [$plant->id => "{$plant->client->name} — {$plant->name}"],
                    )->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('track', fn (Builder $trackQuery): Builder => $trackQuery->where('plant_id', $data['value']))
                        : $query),
                SelectFilter::make('track')
                    ->label('Vía')
                    ->options(fn (): array => Track::query()->with('plant.client')->orderBy('name')->get()->mapWithKeys(
                        fn (Track $track): array => [$track->id => "{$track->plant->client->name} — {$track->plant->name} — {$track->name}"],
                    )->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('track_id', $data['value'])
                        : $query),
                SelectFilter::make('active')->label('Estado')->options([true => 'Activo', false => 'Inactivo']),
            ])
            ->recordActions([
                EditAction::make(),
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
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
