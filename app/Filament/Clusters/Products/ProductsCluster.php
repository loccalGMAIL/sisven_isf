<?php

namespace App\Filament\Clusters\Products;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class ProductsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $clusterBreadcrumb = 'Productos';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
