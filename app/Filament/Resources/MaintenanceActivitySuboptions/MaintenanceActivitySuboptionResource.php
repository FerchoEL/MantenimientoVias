<?php

namespace App\Filament\Resources\MaintenanceActivitySuboptions;

use App\Filament\Resources\MaintenanceActivitySuboptions\Pages\CreateMaintenanceActivitySuboption;
use App\Filament\Resources\MaintenanceActivitySuboptions\Pages\EditMaintenanceActivitySuboption;
use App\Filament\Resources\MaintenanceActivitySuboptions\Pages\ListMaintenanceActivitySuboptions;
use App\Filament\Resources\MaintenanceActivitySuboptions\Schemas\MaintenanceActivitySuboptionForm;
use App\Filament\Resources\MaintenanceActivitySuboptions\Tables\MaintenanceActivitySuboptionsTable;
use App\Models\MaintenanceActivitySuboption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MaintenanceActivitySuboptionResource extends Resource
{
    protected static ?string $model = MaintenanceActivitySuboption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Servicios';

    protected static ?string $navigationLabel = 'Subopciones de actividad';

    protected static ?string $modelLabel = 'subopción de actividad';

    protected static ?string $pluralModelLabel = 'subopciones de actividad';

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('viewAny', MaintenanceActivitySuboption::class) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('concept.category');
        $user = Auth::user();

        if ($user !== null && ! $user->hasRole(['Administrador', 'Jefe de supervisores'], 'web')) {
            $query->where('active', true)->whereHas('concept', fn (Builder $concept): Builder => $concept->where('active', true)->whereHas('category', fn (Builder $category): Builder => $category->where('active', true)));
        }

        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceActivitySuboptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceActivitySuboptionsTable::configure($table);
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
            'index' => ListMaintenanceActivitySuboptions::route('/'),
            'create' => CreateMaintenanceActivitySuboption::route('/create'),
            'edit' => EditMaintenanceActivitySuboption::route('/{record}/edit'),
        ];
    }
}
