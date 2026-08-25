<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use App\Services\BnaExchangeRateFetcher;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;
use Throwable;

class ExchangeRateWidget extends Widget
{
    protected string $view = 'filament.widgets.exchange-rate-widget';

    protected static ?int $sort = 0;

    public function getSetting(): Setting
    {
        return Setting::current();
    }

    public function updateFromBna(BnaExchangeRateFetcher $fetcher): void
    {
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

        Notification::make()
            ->title('Cotización actualizada')
            ->body(sprintf(
                'Venta: %s — Compra: %s',
                Number::currency($rates['venta'], 'ARS', 'es_AR'),
                Number::currency($rates['compra'], 'ARS', 'es_AR'),
            ))
            ->success()
            ->send();
    }
}
