<?php

namespace App\Filament\Resources\MaintenanceActivityCategories;

use App\Filament\Resources\MaintenanceActivityCategories\Pages\CreateMaintenanceActivityCategory;
use App\Filament\Resources\MaintenanceActivityCategories\Pages\EditMaintenanceActivityCategory;
use App\Filament\Resources\MaintenanceActivityCategories\Pages\ListMaintenanceActivityCategories;
use App\Filament\Resources\MaintenanceActivityCategories\Schemas\MaintenanceActivityCategoryForm;
use App\Filament\Resources\MaintenanceActivityCategories\Tables\MaintenanceActivityCategoriesTable;
use App\Models\MaintenanceActivityCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MaintenanceActivityCategoryResource extends Resource
{
    protected static ?string $model = MaintenanceActivityCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Servicios';

    protected static ?string $navigationLabel = 'Categorías de actividad';

    protected static ?string $modelLabel = 'categoría de actividad';

    protected static ?string $pluralModelLabel = 'categorías de actividad';

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('viewAny', MaintenanceActivityCategory::class) ?? false;
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
        return MaintenanceActivityCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceActivityCategoriesTable::configure($table);
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
            'index' => ListMaintenanceActivityCategories::route('/'),
            'create' => CreateMaintenanceActivityCategory::route('/create'),
            'edit' => EditMaintenanceActivityCategory::route('/{record}/edit'),
        ];
    }
}
