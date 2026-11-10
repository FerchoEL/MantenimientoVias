<?php

namespace App\Filament\Resources\MaintenanceServices\Tables;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use App\MaintenanceServiceStatus;
use App\Models\Client;
use App\Models\MaintenanceService;
use App\ReportFrequency;
use Filament\Actions\Action;
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
                ->options(fn (): array => Client::query()
                    ->when(! auth()->user()?->hasRole(['Administrador', 'Jefe de supervisores'], 'web'),
                        fn (Builder $clients): Builder => $clients->whereHas('plants.maintenanceServices', fn (Builder $services): Builder => $services->visibleTo(auth()->user())))
                    ->orderBy('name')->pluck('name', 'id')->all())
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->whereHas('plant', fn (Builder $plant): Builder => $plant->where('client_id', $data['value'])) : $query),
            SelectFilter::make('plant')->label('Planta')->relationship('plant', 'name',
                fn (Builder $query): Builder => $query->when(! auth()->user()?->hasRole(['Administrador', 'Jefe de supervisores'], 'web'),
                    fn (Builder $plants): Builder => $plants->whereHas('maintenanceServices', fn (Builder $services): Builder => $services->visibleTo(auth()->user()))))
                ->searchable()->preload(),
            SelectFilter::make('report_frequency')->label('Periodicidad')->options(ReportFrequency::class),
            SelectFilter::make('status')->label('Estado')->options(MaintenanceServiceStatus::class),
        ])->recordActions(array_merge(
            [Action::make('view')->label('Consultar Servicio')
                ->url(fn (MaintenanceService $record): string => MaintenanceServiceResource::getUrl('view', ['record' => $record])),
                EditAction::make()->label('Editar Servicio')],
            MaintenanceServiceResource::statusActions(),
        ))
            ->recordUrl(fn (MaintenanceService $record): string => MaintenanceServiceResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('No hay Servicios disponibles')
            ->emptyStateDescription('Solo se muestran los Servicios que puedes consultar. Si eres Supervisor, necesitas una asignación vigente y habilitada.')
            ->defaultSort('start_date', 'desc');
    }
}
