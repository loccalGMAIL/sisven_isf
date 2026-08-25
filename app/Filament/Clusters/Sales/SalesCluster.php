<?php

namespace App\Filament\Clusters\Sales;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class SalesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $clusterBreadcrumb = 'Ventas';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
