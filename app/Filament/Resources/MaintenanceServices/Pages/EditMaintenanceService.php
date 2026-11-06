<?php

namespace App\Filament\Resources\MaintenanceServices\Pages;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditMaintenanceService extends EditRecord
{
    protected static string $resource = MaintenanceServiceResource::class;

    protected static ?string $title = 'Editar Servicio';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, ['name', 'order_number', 'start_date', 'end_date', 'report_frequency', 'notes']);
    }
}
