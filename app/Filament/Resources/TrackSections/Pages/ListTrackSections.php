<?php

namespace App\Filament\Resources\TrackSections\Pages;

use App\Filament\Resources\TrackSections\TrackSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackSections extends ListRecords
{
    protected static string $resource = TrackSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
