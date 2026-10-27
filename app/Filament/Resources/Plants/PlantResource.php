<?php

namespace App\Filament\Resources\Plants;

use App\Filament\Resources\Plants\Pages\ManagePlants;
use App\Models\Plant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class PlantResource extends Resource
{
    protected static ?string $model = Plant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Plantas';

    protected static ?string $modelLabel = 'planta';

    protected static ?string $pluralModelLabel = 'plantas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label('Cliente')
                    ->relationship(
                        'client',
                        'name',
                        fn (Builder $query, Select $component): Builder => $query->where(function (Builder $query) use ($component): void {
                            $query->where('active', true);

                            $record = $component->getRecord();

                            if ($record instanceof Plant && $record->client_id !== null) {
                                $query->orWhereKey($record->client_id);
                            }
                        }),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages(['required' => 'El cliente es obligatorio.'])
                    ->helperText(fn (?Plant $record): ?string => $record?->client?->active === false
                        ? 'Este cliente se encuentra inactivo. La información registrada se conserva como parte del historial. Evite modificar datos históricos salvo que exista una corrección administrativa justificada.'
                        : null),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150)
                    ->unique(
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                            'client_id',
                            $get('client_id'),
                        ),
                    )
                    ->validationMessages([
                        'required' => 'El nombre es obligatorio.',
                        'max' => 'El nombre no puede superar los 150 caracteres.',
                        'unique' => 'Ya existe una planta con ese nombre para el cliente seleccionado.',
                    ]),
                TextInput::make('short_name')
                    ->label('Nombre corto')
                    ->maxLength(80)
                    ->validationMessages(['max' => 'El nombre corto no puede superar los 80 caracteres.']),
                Toggle::make('active')->label('Activo')->default(true),
                Textarea::make('observations')->label('Observaciones')->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('short_name')->label('Nombre corto')->searchable(),
                IconColumn::make('active')->label('Activo')->boolean(),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('client')->label('Cliente')->relationship('client', 'name')->searchable()->preload()])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Plant $record): string => $record->active ? 'Desactivar' : 'Activar')
                    ->icon(fn (Plant $record): Heroicon => $record->active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (Plant $record): string => $record->active ? 'danger' : 'success')
                    ->authorize(fn (Plant $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(fn (Plant $record): bool => $record->update(['active' => ! $record->active])),
            ])
            ->toolbarActions([BulkActionGroup::make([])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlants::route('/'),
        ];
    }
}
