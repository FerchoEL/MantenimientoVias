<?php

namespace App\Filament\Resources\MaintenanceUnits\Pages;

use App\Filament\Resources\MaintenanceUnits\MaintenanceUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceUnits extends ListRecords
{
    protected static string $resource = MaintenanceUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
