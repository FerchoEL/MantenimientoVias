<?php

namespace App\Filament\Resources\MaintenanceUnits;

use App\Filament\Resources\MaintenanceUnits\Pages\CreateMaintenanceUnit;
use App\Filament\Resources\MaintenanceUnits\Pages\EditMaintenanceUnit;
use App\Filament\Resources\MaintenanceUnits\Pages\ListMaintenanceUnits;
use App\Filament\Resources\MaintenanceUnits\Schemas\MaintenanceUnitForm;
use App\Filament\Resources\MaintenanceUnits\Tables\MaintenanceUnitsTable;
use App\Models\MaintenanceUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MaintenanceUnitResource extends Resource
{
    protected static ?string $model = MaintenanceUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Servicios';

    protected static ?string $navigationLabel = 'Unidades de medida';

    protected static ?string $modelLabel = 'unidad de medida';

    protected static ?string $pluralModelLabel = 'unidades de medida';

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('viewAny', MaintenanceUnit::class) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user !== null && ! $user->hasRole(['Administrador', 'Jefe de supervisores'], 'web')) {
            $query->activeOrdered();
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceUnitsTable::configure($table);
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
            'index' => ListMaintenanceUnits::route('/'),
            'create' => CreateMaintenanceUnit::route('/create'),
            'edit' => EditMaintenanceUnit::route('/{record}/edit'),
        ];
    }
}
