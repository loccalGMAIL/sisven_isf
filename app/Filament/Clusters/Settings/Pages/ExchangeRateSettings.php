<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Setting;
use App\Services\BnaExchangeRateFetcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;
use Throwable;

class ExchangeRateSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Cotización';

    protected static ?string $title = 'Cotización del dólar';

    protected static ?string $cluster = SettingsCluster::class;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::current()->only(['dollar_rate', 'dollar_rounding']));
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dollar_rate')
                    ->label('Tipo de cambio')
                    ->helperText('Cantidad de pesos por cada dólar.')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix('$')
                    ->required(),
                TextInput::make('dollar_rounding')
                    ->label('Redondeo')
                    ->helperText('El monto en dólares se redondea al múltiplo más cercano de este valor. Ej: 0.50')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix('US$')
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::current()->update($data);

        Notification::make()
            ->title('Cotización actualizada')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchBnaRate')
                ->label('Actualizar cotización BNA')
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->action(function (BnaExchangeRateFetcher $fetcher): void {
                    try {
                        $rates = $fetcher->fetch();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('No se pudo obtener la cotización del BNA')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Setting::current()->update(['dollar_rate' => $rates['venta']]);

                    $this->form->fill(Setting::current()->only(['dollar_rate', 'dollar_rounding']));

                    Notification::make()
                        ->title('Cotización BNA actualizada')
                        ->body(sprintf(
                            'Venta: %s — Compra: %s',
                            Number::currency($rates['venta'], 'ARS', 'es_AR'),
                            Number::currency($rates['compra'], 'ARS', 'es_AR'),
                        ))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }
}
