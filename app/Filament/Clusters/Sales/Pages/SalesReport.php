<?php

namespace App\Filament\Clusters\Sales\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Clusters\Sales\SalesCluster;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $cluster = SalesCluster::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Informes';

    protected static ?string $title = 'Informe de ventas';

    protected static ?string $slug = 'informes';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'user_ids' => [],
            'payment_methods' => [],
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                DatePicker::make('date_from')
                    ->label('Desde')
                    ->native(false)
                    ->live()
                    ->required(),
                DatePicker::make('date_to')
                    ->label('Hasta')
                    ->native(false)
                    ->live()
                    ->required(),
                Select::make('user_ids')
                    ->label('Vendedores')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->placeholder('Todos'),
                Select::make('payment_methods')
                    ->label('Medio de pago')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()]))
                    ->multiple()
                    ->live()
                    ->placeholder('Todos'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('form'),
                View::make('filament.pages.sales-report-summary')
                    ->viewData(fn (): array => ['summary' => $this->getSummary()]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCsv')
                ->label('Descargar CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
            Action::make('downloadPdf')
                ->label('Descargar PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('primary')
                ->action(fn () => $this->downloadPdf()),
        ];
    }

    protected function filteredSalesQuery(): Builder
    {
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;
        $userIds = array_filter((array) ($this->data['user_ids'] ?? []));
        $paymentMethods = array_filter((array) ($this->data['payment_methods'] ?? []));

        return Sale::query()
            ->with(['user', 'saleDetails.product'])
            ->when($dateFrom, fn (Builder $query, string $date): Builder => $query->whereDate('date', '>=', $date))
            ->when($dateTo, fn (Builder $query, string $date): Builder => $query->whereDate('date', '<=', $date))
            ->when($userIds !== [], fn (Builder $query): Builder => $query->whereIn('user_id', $userIds))
            ->when($paymentMethods !== [], fn (Builder $query): Builder => $query->whereIn('payment_method', $paymentMethods))
            ->orderBy('date');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSummary(): array
    {
        /** @var Collection<int, Sale> $sales */
        $sales = $this->filteredSalesQuery()->get();

        $byPaymentMethod = $sales
            ->groupBy(fn (Sale $sale) => $sale->payment_method?->value ?? 'sin_definir')
            ->map(fn (Collection $group, string $key): array => [
                'label' => PaymentMethod::tryFrom($key)?->label() ?? 'Sin definir',
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
                'usdTotal' => $key === PaymentMethod::Dolares->value ? (float) $group->sum('amount_usd') : null,
            ])
            ->sortByDesc('total');

        $byUser = $sales
            ->groupBy(fn (Sale $sale) => $sale->user?->name ?? 'Sin vendedor')
            ->map(fn (Collection $group): array => [
                'count' => $group->count(),
                'total' => (float) $group->sum('total'),
                'usdTotal' => (float) $group->where('payment_method', PaymentMethod::Dolares)->sum('amount_usd'),
            ])
            ->sortByDesc('total');

        $products = $sales
            ->flatMap(fn (Sale $sale): iterable => $sale->saleDetails)
            ->groupBy(fn (SaleDetail $detail) => $detail->product?->name ?? 'Producto eliminado')
            ->map(fn (BaseCollection $group): array => [
                'quantity' => (int) $group->sum('quantity'),
                'subtotal' => (float) $group->sum(fn (SaleDetail $detail): float => $detail->quantity * (float) $detail->price),
            ])
            ->sortByDesc('subtotal');

        $dollarSales = $sales->where('payment_method', PaymentMethod::Dolares);

        return [
            'dateFrom' => $this->data['date_from'] ?? null,
            'dateTo' => $this->data['date_to'] ?? null,
            'count' => $sales->count(),
            'total' => (float) $sales->sum('total'),
            'usdCount' => $dollarSales->count(),
            'usdTotal' => (float) $dollarSales->sum('amount_usd'),
            'byPaymentMethod' => $byPaymentMethod,
            'byUser' => $byUser,
            'products' => $products,
        ];
    }

    protected function downloadCsv(): StreamedResponse
    {
        $sales = $this->filteredSalesQuery()->get();
        $filename = 'informe-ventas-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($sales): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Fecha', 'Vendedor', 'Medio de pago', 'Total', 'Monto USD']);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->date?->format('d/m/Y'),
                    $sale->user?->name ?? '—',
                    $sale->payment_method?->label() ?? '—',
                    number_format((float) $sale->total, 2, ',', '.'),
                    $sale->payment_method === PaymentMethod::Dolares && $sale->amount_usd !== null
                        ? number_format((float) $sale->amount_usd, 2, ',', '.')
                        : '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function downloadPdf(): StreamedResponse
    {
        $summary = $this->getSummary();
        $filename = 'informe-ventas-'.now()->format('Y-m-d-His').'.pdf';

        $pdf = Pdf::loadView('pdf.sales-report', ['summary' => $summary])
            ->setPaper('a4', 'portrait');

        return Response::streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
