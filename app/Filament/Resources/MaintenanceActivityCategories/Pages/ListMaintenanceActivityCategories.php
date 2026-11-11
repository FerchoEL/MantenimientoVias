<?php

namespace App\Filament\Resources\MaintenanceActivityCategories\Pages;

use App\Filament\Resources\MaintenanceActivityCategories\MaintenanceActivityCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceActivityCategories extends ListRecords
{
    protected static string $resource = MaintenanceActivityCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
