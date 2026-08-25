<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Cobrar');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.sales.product-cards')
                    ->viewData(['products' => Product::query()->where('active', true)->orderBy('name')->get()]),
                $this->getSaleDetailsTable(),
                SaleResource::getTotalField(),
            ]);
    }

    protected function getSaleDetailsTable(): Repeater
    {
        return Repeater::make('saleDetails')
            ->relationship()
            ->label('Detalle')
            ->table([
                TableColumn::make('Producto'),
                TableColumn::make('Cantidad')->width('60px')->alignment(Alignment::End),
                TableColumn::make('Precio')->width('150px')->alignment(Alignment::End),
            ])
            ->schema([
                Hidden::make('product_id'),
                Hidden::make('price'),
                TextInput::make('product_name')
                    ->label('Producto')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->extraInputAttributes(['class' => 'text-end']),
                TextInput::make('subtotal')
                    ->label('Precio')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false)
                    ->extraInputAttributes(['class' => 'text-end']),
            ])
            ->compact()
            ->addable(false)
            ->reorderable(false)
            ->deleteAction(
                fn (Action $action) => $action->after(fn () => $this->recalculateTotal()),
            )
            ->minItems(1)
            ->required()
            ->columnSpanFull();
    }

    public function addProductToSaleAction(): Action
    {
        return Action::make('addProductToSale')
            ->label('Agregar producto')
            ->modalHeading(fn (array $arguments): string => 'Agregar '.(Product::find($arguments['product'] ?? null)?->name ?? 'producto'))
            ->modalSubmitActionLabel('Agregar')
            ->schema([
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $product = Product::findOrFail($arguments['product']);

                $this->data['saleDetails'] = collect($this->data['saleDetails'] ?? [])
                    ->reject(fn (array $detail): bool => blank($detail['product_id'] ?? null))
                    ->toArray();

                $this->data['saleDetails'][(string) Str::uuid()] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $data['quantity'],
                    'price' => $product->price,
                    'subtotal' => number_format($data['quantity'] * $product->price, 2, '.', ''),
                ];

                $this->recalculateTotal();
            });
    }

    protected function recalculateTotal(): void
    {
        $total = collect($this->data['saleDetails'] ?? [])
            ->sum(fn (array $detail): float => (float) ($detail['quantity'] ?? 0) * (float) ($detail['price'] ?? 0));

        $this->data['total'] = number_format($total, 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['date'] = now();

        return $data;
    }
}
