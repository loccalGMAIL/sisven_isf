<?php

namespace App\Filament\Widgets;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use Filament\Widgets\Widget;

class QuickSaleWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-sale-widget';

    protected static ?int $sort = -1;

    public function getSaleUrl(): string
    {
        return SaleResource::getUrl('create');
    }
}
