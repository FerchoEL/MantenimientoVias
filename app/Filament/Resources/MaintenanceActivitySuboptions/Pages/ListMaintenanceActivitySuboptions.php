<?php

namespace App\Filament\Resources\MaintenanceActivitySuboptions\Pages;

use App\Filament\Resources\MaintenanceActivitySuboptions\MaintenanceActivitySuboptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceActivitySuboptions extends ListRecords
{
    protected static string $resource = MaintenanceActivitySuboptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
