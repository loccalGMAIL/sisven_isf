<?php

namespace App\Filament\Clusters\Sales\Resources\Sales;

use App\Enums\PaymentMethod;
use App\Filament\Clusters\Sales\Resources\Sales\Pages\CreateSale;
use App\Filament\Clusters\Sales\Resources\Sales\Pages\ManageSales;
use App\Filament\Clusters\Sales\SalesCluster;
use App\Models\Product;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'venta';

    protected static ?string $pluralModelLabel = 'ventas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Vendedor')
                    ->relationship('user', 'name')
                    ->default(fn (): ?int => auth()->id())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->default(now())
                    ->required(),
                static::getSaleDetailsRepeater(),
                static::getTotalField(),
            ]);
    }

    public static function getSaleDetailsRepeater(): Repeater
    {
        return Repeater::make('saleDetails')
            ->relationship()
            ->label('Detalle')
            ->schema([
                Select::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        $set('price', Product::find($get('product_id'))?->price);
                        static::updateTotal($get, $set);
                    })
                    ->required(),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::updateTotal($get, $set))
                    ->required(),
                TextInput::make('price')
                    ->label('Precio unitario')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => static::updateTotal($get, $set))
                    ->required(),
            ])
            ->columns(3)
            ->minItems(1)
            ->addActionLabel('Agregar producto')
            ->deleteAction(
                fn (Action $action) => $action->after(fn (Get $get, Set $set) => static::updateTotal($get, $set)),
            )
            ->required()
            ->columnSpanFull();
    }

    public static function getTotalField(): TextInput
    {
        return TextInput::make('total')
            ->label('Total')
            ->numeric()
            ->prefix('$')
            ->readOnly()
            ->required();
    }

    public static function updateTotal(Get $get, Set $set): void
    {
        $details = collect($get('../../saleDetails') ?? []);

        $total = $details->sum(fn (array $detail): float => (float) ($detail['quantity'] ?? 0) * (float) ($detail['price'] ?? 0));

        $set('../../total', number_format($total, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Pago')
                    ->badge()
                    ->formatStateUsing(fn (?PaymentMethod $state): string => $state?->label() ?? '—')
                    ->color(fn (?PaymentMethod $state): string => $state?->color() ?? 'gray'),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewReceipt')
                    ->label('Comprobante')
                    ->icon(Heroicon::Photo)
                    ->url(fn (Sale $record): string => Storage::disk('public')->url($record->receipt_path))
                    ->openUrlInNewTab()
                    ->visible(fn (Sale $record): bool => filled($record->receipt_path)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSales::route('/'),
            'create' => CreateSale::route('/create'),
        ];
    }
}
