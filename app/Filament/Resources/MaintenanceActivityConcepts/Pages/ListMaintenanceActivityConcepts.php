<?php

namespace App\Filament\Resources\MaintenanceActivityConcepts\Pages;

use App\Filament\Resources\MaintenanceActivityConcepts\MaintenanceActivityConceptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceActivityConcepts extends ListRecords
{
    protected static string $resource = MaintenanceActivityConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
