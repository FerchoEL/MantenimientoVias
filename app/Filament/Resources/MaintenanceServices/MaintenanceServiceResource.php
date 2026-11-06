<?php

namespace App\Filament\Resources\MaintenanceServices;

use App\Filament\Resources\MaintenanceServices\Pages\CreateMaintenanceService;
use App\Filament\Resources\MaintenanceServices\Pages\EditMaintenanceService;
use App\Filament\Resources\MaintenanceServices\Pages\ListMaintenanceServices;
use App\Filament\Resources\MaintenanceServices\Schemas\MaintenanceServiceForm;
use App\Filament\Resources\MaintenanceServices\Tables\MaintenanceServicesTable;
use App\MaintenanceServiceStatus;
use App\Models\MaintenanceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class MaintenanceServiceResource extends Resource
{
    protected static ?string $model = MaintenanceService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Servicios';

    protected static ?string $navigationLabel = 'Servicios de mantenimiento';

    protected static ?string $modelLabel = 'Servicio de mantenimiento';

    protected static ?string $pluralModelLabel = 'Servicios de mantenimiento';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('plant.client');
    }

    public static function form(Schema $schema): Schema
    {
        return MaintenanceServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceServicesTable::configure($table);
    }

    /** @return array<int, Action> */
    public static function statusActions(): array
    {
        return [
            Action::make('startService')
                ->label('Iniciar Servicio')
                ->requiresConfirmation()
                ->visible(fn (MaintenanceService $record): bool => $record->status === MaintenanceServiceStatus::Planned)
                ->authorize(fn (MaintenanceService $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(function (MaintenanceService $record): void {
                    static::transition($record, MaintenanceServiceStatus::Active);
                }),
            Action::make('completeService')
                ->label('Finalizar Servicio')
                ->requiresConfirmation()
                ->visible(fn (MaintenanceService $record): bool => $record->status === MaintenanceServiceStatus::Active)
                ->authorize(fn (MaintenanceService $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(function (MaintenanceService $record): void {
                    static::transition($record, MaintenanceServiceStatus::Completed);
                }),
            Action::make('cancelService')
                ->label('Cancelar Servicio')
                ->requiresConfirmation()
                ->visible(fn (MaintenanceService $record): bool => $record->status === MaintenanceServiceStatus::Active)
                ->authorize(fn (MaintenanceService $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->action(function (MaintenanceService $record): void {
                    static::transition($record, MaintenanceServiceStatus::Cancelled);
                }),
        ];
    }

    private static function transition(MaintenanceService $record, MaintenanceServiceStatus $target): void
    {
        try {
            $changed = $record->transitionTo($target);
        } catch (ValidationException $exception) {
            Notification::make()->title($exception->validator->errors()->first())->danger()->send();

            return;
        }

        if ($changed) {
            Notification::make()->title("Servicio {$target->getLabel()}.")->success()->send();
        }
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
            'index' => ListMaintenanceServices::route('/'),
            'create' => CreateMaintenanceService::route('/create'),
            'edit' => EditMaintenanceService::route('/{record}/edit'),
        ];
    }
}
