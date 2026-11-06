<?php

namespace App\Filament\Resources\MaintenanceServices\Pages;

use App\Filament\Resources\MaintenanceServices\MaintenanceServiceResource;
use App\MaintenanceServiceStatus;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateMaintenanceService extends CreateRecord
{
    protected static string $resource = MaintenanceServiceResource::class;

    protected static ?string $title = 'Crear Servicio';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = Arr::only($data, ['plant_id', 'name', 'order_number', 'start_date', 'end_date', 'report_frequency', 'notes']);
        $data['status'] = MaintenanceServiceStatus::Planned;

        return $data;
    }
}
