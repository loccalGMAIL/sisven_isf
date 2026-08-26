<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Sale;
use App\Models\SaleDetail;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReset extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Reiniciar ventas';

    protected static ?string $title = 'Reiniciar ventas';

    protected static ?string $slug = 'reiniciar-ventas';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getSalesCount(): int
    {
        return Sale::count();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('Esta acción no se puede deshacer')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->description('Elimina todas las ventas y sus detalles, y los contadores (totales, informes) vuelven a $0. Los usuarios y productos no se ven afectados. Antes de borrar se genera un respaldo con el historial completo: se descarga automáticamente y además queda guardado en el servidor.'),
                Text::make(function (): string {
                    $count = $this->getSalesCount();

                    return $count === 1
                        ? 'Actualmente hay 1 venta registrada.'
                        : "Actualmente hay {$count} ventas registradas.";
                })
                    ->weight('medium'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetSales')
                ->label('Reiniciar ventas')
                ->color('danger')
                ->icon(Heroicon::OutlinedTrash)
                ->disabled(fn (): bool => $this->getSalesCount() === 0)
                ->modalHeading('Reiniciar ventas y contadores')
                ->modalDescription('Se eliminarán todas las ventas y sus detalles, y los contadores (totales, informes) vuelven a $0. Los usuarios y productos no se ven afectados. Antes de borrar se genera un respaldo con el historial completo, que se descarga automáticamente y además queda guardado en el servidor. Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Reiniciar ventas')
                ->schema([
                    TextInput::make('confirmation')
                        ->label('Para confirmar, escribí REINICIAR')
                        ->required()
                        ->rule('in:REINICIAR')
                        ->validationMessages([
                            'in' => 'Escribí REINICIAR (en mayúsculas) para confirmar.',
                        ]),
                ])
                ->action(fn (): StreamedResponse => $this->resetSales()),
        ];
    }

    protected function resetSales(): StreamedResponse
    {
        $sales = Sale::query()
            ->with(['user', 'saleDetails.product'])
            ->orderBy('date')
            ->get();

        $count = $sales->count();

        $backup = [
            'generated_at' => now(config('app.display_timezone'))->toIso8601String(),
            'generated_by' => auth()->user()?->name,
            'count' => $count,
            'sales' => $sales->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'user' => $sale->user?->name,
                'total' => (string) $sale->total,
                'date' => $sale->date?->toDateString(),
                'payment_method' => $sale->payment_method?->value,
                'exchange_rate' => $sale->exchange_rate !== null ? (string) $sale->exchange_rate : null,
                'amount_usd' => $sale->amount_usd !== null ? (string) $sale->amount_usd : null,
                'receipt_path' => $sale->receipt_path,
                'created_at' => $sale->created_at?->toIso8601String(),
                'details' => $sale->saleDetails->map(fn (SaleDetail $detail): array => [
                    'product' => $detail->product?->name,
                    'quantity' => $detail->quantity,
                    'price' => (string) $detail->price,
                ])->all(),
            ])->all(),
        ];

        $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'ventas-respaldo-'.now(config('app.display_timezone'))->format('Y-m-d-His').'.json';

        Storage::disk('local')->put('sales-backups/'.$filename, $json);

        DB::table('sale_details')->truncate();
        DB::table('sales')->truncate();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['count' => $count, 'backup' => $filename])
            ->log("Reinició las ventas: se archivaron {$count} ventas antes de borrarlas.");

        Notification::make()
            ->title('Ventas reiniciadas')
            ->body("Se respaldaron {$count} ventas y se reiniciaron los contadores.")
            ->success()
            ->send();

        return Response::streamDownload(
            fn () => print ($json),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
