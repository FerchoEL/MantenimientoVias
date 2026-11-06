<?php

namespace App\Filament\Resources\MaintenanceServices\Schemas;

use App\Models\Client;
use App\Models\MaintenanceService;
use App\ReportFrequency;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class MaintenanceServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('client_id')->label('Cliente')
                ->options(fn (): array => Client::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->required()->searchable()->live()->dehydrated(false)->visibleOn('create')
                ->afterStateUpdated(fn (Set $set) => $set('plant_id', null))
                ->rules([Rule::exists('clients', 'id')->where('active', true)]),
            Select::make('plant_id')->label('Planta')->visibleOn('create')
                ->relationship('plant', 'name', fn (Builder $query, Get $get): Builder => $query
                    ->where('client_id', $get('client_id'))->where('active', true)
                    ->whereHas('client', fn (Builder $client): Builder => $client->where('active', true)))
                ->required()->searchable()->preload()
                ->rules(fn (Get $get): array => [Rule::exists('plants', 'id')->where('client_id', $get('client_id'))->where('active', true)]),
            TextEntry::make('client_display')->label('Cliente')->visibleOn('edit')
                ->state(fn (?MaintenanceService $record): ?string => $record?->plant->client->name),
            TextEntry::make('plant_display')->label('Planta')->visibleOn('edit')
                ->state(fn (?MaintenanceService $record): ?string => $record?->plant->name),
            TextInput::make('name')->label('Nombre del Servicio')->required()->maxLength(150),
            TextInput::make('order_number')->label('Pedido o contrato')->maxLength(80)->nullable(),
            DatePicker::make('start_date')->label('Fecha de inicio')->required(),
            DatePicker::make('end_date')->label('Fecha de terminación')->nullable()->afterOrEqual('start_date'),
            Select::make('report_frequency')->label('Periodicidad')->options(ReportFrequency::class)
                ->required()->rules([Rule::enum(ReportFrequency::class)]),
            TextEntry::make('status_display')->label('Estado')
                ->state(fn (?MaintenanceService $record): string => $record?->status->getLabel() ?? 'Planeado'),
            Textarea::make('notes')->label('Observaciones')->nullable()->columnSpanFull(),
        ]);
    }
}
