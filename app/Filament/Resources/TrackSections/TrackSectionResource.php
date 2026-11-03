<?php

namespace App\Filament\Resources\TrackSections;

use App\Filament\Resources\TrackSections\Pages\CreateTrackSection;
use App\Filament\Resources\TrackSections\Pages\EditTrackSection;
use App\Filament\Resources\TrackSections\Pages\ListTrackSections;
use App\Filament\Resources\TrackSections\Schemas\TrackSectionForm;
use App\Filament\Resources\TrackSections\Tables\TrackSectionsTable;
use App\Models\TrackSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TrackSectionResource extends Resource
{
    protected static ?string $model = TrackSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Tramos';

    protected static ?string $modelLabel = 'tramo';

    protected static ?string $pluralModelLabel = 'tramos';

    public static function form(Schema $schema): Schema
    {
        return TrackSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrackSectionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['track.plant.client']);
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
            'index' => ListTrackSections::route('/'),
            'create' => CreateTrackSection::route('/create'),
            'edit' => EditTrackSection::route('/{record}/edit'),
        ];
    }
}
