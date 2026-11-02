<?php

namespace App\Filament\Resources\TrackSwitches\Pages;

use App\Filament\Resources\TrackSwitches\TrackSwitchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackSwitches extends ListRecords
{
    protected static string $resource = TrackSwitchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
