<?php

namespace App\Filament\Resources\MaintenanceServices\Pages;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceServices extends ListRecords
{
    protected static string $resource = MaintenanceServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Crear Servicio'),
        ];
    }
}
