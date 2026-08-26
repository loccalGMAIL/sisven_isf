<?php

use App\Enums\PaymentMethod;
use App\Filament\Clusters\Sales\Resources\Sales\Pages\CreateSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('serves receipts from a folder inside the public directory', function (): void {
    expect(config('filesystems.disks.receipts.root'))->toBe(public_path('comprobantes'))
        ->and(Storage::disk('receipts')->url('foo.jpg'))->toEndWith('/comprobantes/foo.jpg');
});

it('stores the transfer receipt on the receipts disk', function (): void {
    Storage::fake('receipts');

    $user = User::factory()->create();
    $product = Product::factory()->create(['active' => true, 'price' => 1500]);

    Gate::before(fn (): bool => true);

    $this->actingAs($user);

    Filament::setCurrentPanel('dashboard');

    Livewire::test(CreateSale::class)
        ->callAction('addProductToSale', data: ['quantity' => 2], arguments: ['product' => $product->id])
        ->set('paymentReceipt', UploadedFile::fake()->image('comprobante.jpg'))
        ->call('chargeWith', 'transferencia')
        ->assertHasNoErrors();

    $sale = Sale::query()->latest('id')->firstOrFail();

    expect($sale->payment_method)->toBe(PaymentMethod::Transferencia)
        ->and($sale->receipt_path)->not->toBeNull()
        ->and($sale->receipt_path)->not->toContain('/');

    Storage::disk('receipts')->assertExists($sale->receipt_path);
});

it('moves legacy receipts out of the storage folder', function (): void {
    Storage::fake('public');
    Storage::fake('receipts');

    Storage::disk('public')->put('sale-receipts/legacy.jpg', 'contenido');

    $sale = Sale::factory()->create(['receipt_path' => 'sale-receipts/legacy.jpg']);

    (include database_path('migrations/2026_08_26_120000_move_sale_receipts_to_public_folder.php'))->up();

    expect($sale->fresh()->receipt_path)->toBe('legacy.jpg');

    Storage::disk('receipts')->assertExists('legacy.jpg');
    Storage::disk('public')->assertMissing('sale-receipts/legacy.jpg');
});
