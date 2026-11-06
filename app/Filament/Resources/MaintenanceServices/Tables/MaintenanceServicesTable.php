<?php

namespace App\Filament\Resources\MaintenanceServices\Tables;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use App\MaintenanceServiceStatus;
use App\Models\Client;
use App\ReportFrequency;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('plant.client.name')->label('Cliente'),
            TextColumn::make('plant.name')->label('Planta'),
            TextColumn::make('name')->label('Nombre del Servicio')->searchable(),
            TextColumn::make('order_number')->label('Pedido o contrato')->searchable(),
            TextColumn::make('start_date')->label('Fecha inicial')->date('d/m/Y')->sortable(),
            TextColumn::make('end_date')->label('Fecha final')->date('d/m/Y'),
            TextColumn::make('report_frequency')->label('Periodicidad'),
            TextColumn::make('status')->label('Estado')->badge(),
        ])->filters([
            SelectFilter::make('client')->label('Cliente')
                ->options(fn (): array => Client::query()->orderBy('name')->pluck('name', 'id')->all())
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->whereHas('plant', fn (Builder $plant): Builder => $plant->where('client_id', $data['value'])) : $query),
            SelectFilter::make('plant')->label('Planta')->relationship('plant', 'name')->searchable()->preload(),
            SelectFilter::make('report_frequency')->label('Periodicidad')->options(ReportFrequency::class),
            SelectFilter::make('status')->label('Estado')->options(MaintenanceServiceStatus::class),
        ])->recordActions(array_merge(
            [EditAction::make()->label('Editar Servicio')],
            MaintenanceServiceResource::statusActions(),
        ))
            ->defaultSort('start_date', 'desc');
    }
}
