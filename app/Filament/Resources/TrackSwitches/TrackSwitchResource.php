<?php

namespace App\Filament\Resources\TrackSwitches;

use App\Filament\Resources\TrackSwitches\Pages\CreateTrackSwitch;
use App\Filament\Resources\TrackSwitches\Pages\EditTrackSwitch;
use App\Filament\Resources\TrackSwitches\Pages\ListTrackSwitches;
use App\Filament\Resources\TrackSwitches\Schemas\TrackSwitchForm;
use App\Filament\Resources\TrackSwitches\Tables\TrackSwitchesTable;
use App\Models\TrackSwitch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TrackSwitchResource extends Resource
{
    protected static ?string $model = TrackSwitch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Cambios de vía';

    protected static ?string $modelLabel = 'cambio de vía';

    protected static ?string $pluralModelLabel = 'cambios de vía';

    public static function form(Schema $schema): Schema
    {
        return TrackSwitchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrackSwitchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackSwitches::route('/'),
            'create' => CreateTrackSwitch::route('/create'),
            'edit' => EditTrackSwitch::route('/{record}/edit'),
        ];
    }
}
