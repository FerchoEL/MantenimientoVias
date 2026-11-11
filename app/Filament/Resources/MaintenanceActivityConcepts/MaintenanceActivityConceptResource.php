<?php

namespace App\Filament\Resources\MaintenanceActivityConcepts;

use App\Filament\Resources\MaintenanceActivityConcepts\Pages\CreateMaintenanceActivityConcept;
use App\Filament\Resources\MaintenanceActivityConcepts\Pages\EditMaintenanceActivityConcept;
use App\Filament\Resources\MaintenanceActivityConcepts\Pages\ListMaintenanceActivityConcepts;
use App\Filament\Resources\MaintenanceActivityConcepts\Schemas\MaintenanceActivityConceptForm;
use App\Filament\Resources\MaintenanceActivityConcepts\Tables\MaintenanceActivityConceptsTable;
use App\Models\MaintenanceActivityConcept;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MaintenanceActivityConceptResource extends Resource
{
    protected static ?string $model = MaintenanceActivityConcept::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Servicios';

    protected static ?string $navigationLabel = 'Conceptos de actividad';

    protected static ?string $modelLabel = 'concepto de actividad';

    protected static ?string $pluralModelLabel = 'conceptos de actividad';

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('viewAny', MaintenanceActivityConcept::class) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('category');
        $user = Auth::user();

        if ($user !== null && ! $user->hasRole(['Administrador', 'Jefe de supervisores'], 'web')) {
            $query->where('active', true)->whereHas('category', fn (Builder $category): Builder => $category->where('active', true));
        }

        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceActivityConceptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceActivityConceptsTable::configure($table);
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
            'index' => ListMaintenanceActivityConcepts::route('/'),
            'create' => CreateMaintenanceActivityConcept::route('/create'),
            'edit' => EditMaintenanceActivityConcept::route('/{record}/edit'),
        ];
    }
}
