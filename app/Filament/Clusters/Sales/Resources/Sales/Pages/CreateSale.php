<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Models\Product;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class CreateSale extends CreateRecord
{
    use WithFileUploads;

    protected static string $resource = SaleResource::class;

    protected static bool $canCreateAnother = false;

    public bool $paymentModalOpen = false;

    public mixed $paymentReceipt = null;

    protected ?string $chargingPaymentMethod = null;

    protected ?float $chargingExchangeRate = null;

    protected ?float $chargingAmountUsd = null;

    protected ?string $chargingReceiptPath = null;

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Cobrar')
            ->keyBindings(['mod+s'])
            ->extraAttributes(['class' => 'text-base! text-white! sm:text-sm!'])
            ->action(function (): void {
                $this->form->getState();

                $this->paymentModalOpen = true;
            });
    }

    public function closePaymentModal(): void
    {
        $this->paymentModalOpen = false;
    }

    public function chargeWith(string $method): void
    {
        if (! in_array($method, ['pesos', 'dolares', 'transferencia'], true)) {
            return;
        }

        if ($method === 'transferencia') {
            if (! $this->paymentReceipt) {
                Notification::make()
                    ->title('Adjuntá el comprobante de la transferencia')
                    ->danger()
                    ->send();

                return;
            }

            $this->chargingReceiptPath = $this->paymentReceipt->store('sale-receipts', 'public');
        }

        if ($method === 'dolares') {
            $this->chargingExchangeRate = (float) Setting::current()->dollar_rate;
            $this->chargingAmountUsd = Setting::usdAmountFor($this->currentTotal());
        }

        $this->chargingPaymentMethod = $method;

        $this->create();
    }

    protected function currentTotal(): float
    {
        return (float) ($this->data['total'] ?? 0);
    }

    protected function currentUsdAmount(): float
    {
        return Setting::usdAmountFor($this->currentTotal());
    }

    protected function getRedirectUrl(): string
    {
        return Dashboard::getUrl();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    Group::make([
                        View::make('filament.sales.product-cards')
                            ->viewData(['products' => Product::query()->where('active', true)->orderBy('name')->get()]),
                        $this->getSaleDetailsTable(),
                    ])->extraAttributes(['class' => 'lg:pr-80']),

                    Section::make([
                        View::make('filament.sales.cart-close-button'),
                        Hidden::make('total')
                            ->rule('numeric')
                            ->required(),
                        $this->getTotalDisplayField(),
                        $this->getFormActionsContentComponent(),
                    ])
                        ->heading('Resumen de venta')
                        ->extraAttributes([
                            'x-cloak' => true,
                            'class' => 'fixed inset-y-0 right-0 z-50 w-full max-w-xs overflow-y-auto shadow-xl transition-transform duration-300 ease-in-out lg:translate-x-0 lg:inset-y-auto lg:top-20 lg:bottom-6 lg:rounded-l-xl',
                        ])
                        ->extraAlpineAttributes([
                            ':class' => "saleSummaryOpen ? 'translate-x-0' : 'translate-x-full'",
                        ]),

                    View::make('filament.sales.cart-toggle-button'),
                    View::make('filament.sales.cart-backdrop'),

                    View::make('filament.sales.payment-method-modal')
                        ->viewData(fn (): array => [
                            'paymentModalOpen' => $this->paymentModalOpen,
                            'paymentReceipt' => $this->paymentReceipt,
                            'total' => $this->currentTotal(),
                            'usdAmount' => $this->currentUsdAmount(),
                            'dollarRate' => (float) Setting::current()->dollar_rate,
                        ]),
                ])
                    ->extraAttributes(['x-data' => '{ saleSummaryOpen: false }'])
                    ->columnSpanFull(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName());
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
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
                    ->dehydrated(false)
                    ->extraInputAttributes(['class' => 'text-base sm:text-sm']),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->extraInputAttributes(['class' => 'text-end text-base sm:text-sm']),
                TextInput::make('subtotal')
                    ->label('Precio')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false)
                    ->extraInputAttributes(['class' => 'text-end text-base sm:text-sm']),
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
                    ->integer()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->extraInputAttributes(['class' => 'text-lg sm:text-base']),
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
                    'subtotal' => number_format($data['quantity'] * $product->price, 2, ',', '.'),
                ];

                $this->recalculateTotal();
            });
    }

    protected function getTotalDisplayField(): TextInput
    {
        return TextInput::make('total_display')
            ->label('Total')
            ->prefix('$')
            ->readOnly()
            ->dehydrated(false)
            ->extraInputAttributes(['class' => 'text-lg font-semibold sm:text-base']);
    }

    protected function recalculateTotal(): void
    {
        $total = collect($this->data['saleDetails'] ?? [])
            ->sum(fn (array $detail): float => (float) ($detail['quantity'] ?? 0) * (float) ($detail['price'] ?? 0));

        $this->data['total'] = number_format($total, 2, '.', '');
        $this->data['total_display'] = number_format($total, 2, ',', '.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['date'] = now(config('app.display_timezone'))->toDateString();
        $data['payment_method'] = $this->chargingPaymentMethod;
        $data['exchange_rate'] = $this->chargingExchangeRate;
        $data['amount_usd'] = $this->chargingAmountUsd;
        $data['receipt_path'] = $this->chargingReceiptPath;

        return $data;
    }
}
