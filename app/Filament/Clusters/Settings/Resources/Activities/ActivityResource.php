<?php

namespace App\Filament\Clusters\Settings\Resources\Activities;

use App\Filament\Clusters\Settings\Resources\Activities\Pages\ListActivities;
use App\Filament\Clusters\Settings\Resources\Activities\Tables\ActivitiesTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $navigationLabel = 'Actividad';

    protected static ?string $modelLabel = 'actividad';

    protected static ?string $pluralModelLabel = 'actividad';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextEntry::make('event')
                            ->label('Evento')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime(),
                        TextEntry::make('causer.name')
                            ->label('Realizado por')
                            ->default('Sistema'),
                        TextEntry::make('subject_type')
                            ->label('Modelo')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? (class_basename($state) === 'User' ? 'Usuario' : class_basename($state)) : null),
                    ]),
                TextEntry::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                KeyValueEntry::make('changes')
                    ->label('Cambios')
                    ->state(function (Activity $record): array {
                        $attributes = $record->properties->get('attributes', []);
                        $old = $record->properties->get('old', []);

                        return collect($attributes)
                            ->mapWithKeys(fn ($value, $key) => [
                                $key => ($old[$key] ?? '—').' → '.$value,
                            ])
                            ->toArray();
                    })
                    ->visible(fn (Activity $record): bool => filled($record->properties?->get('attributes')))
                    ->columnSpanFull(),
                KeyValueEntry::make('details')
                    ->label('Detalle')
                    ->state(fn (Activity $record): array => $record->properties?->toArray() ?? [])
                    ->visible(fn (Activity $record): bool => blank($record->properties?->get('attributes')) && filled($record->properties))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
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
            'index' => ListActivities::route('/'),
        ];
    }
}
