<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Move the transfer receipts from the "public" disk (storage/app/public, only
     * reachable through the storage symlink) into public/comprobantes, which is
     * always served directly by the web server.
     */
    public function up(): void
    {
        File::ensureDirectoryExists(public_path('comprobantes'));

        DB::table('sales')
            ->whereNotNull('receipt_path')
            ->where('receipt_path', 'like', 'sale-receipts/%')
            ->orderBy('id')
            ->each(function (object $sale): void {
                $newPath = basename($sale->receipt_path);

                if (Storage::disk('public')->exists($sale->receipt_path)) {
                    Storage::disk('receipts')->put(
                        $newPath,
                        Storage::disk('public')->get($sale->receipt_path),
                    );

                    Storage::disk('public')->delete($sale->receipt_path);
                }

                DB::table('sales')
                    ->where('id', $sale->id)
                    ->update(['receipt_path' => $newPath]);
            });
    }

    public function down(): void
    {
        DB::table('sales')
            ->whereNotNull('receipt_path')
            ->where('receipt_path', 'not like', '%/%')
            ->orderBy('id')
            ->each(function (object $sale): void {
                $oldPath = 'sale-receipts/'.$sale->receipt_path;

                if (Storage::disk('receipts')->exists($sale->receipt_path)) {
                    Storage::disk('public')->put(
                        $oldPath,
                        Storage::disk('receipts')->get($sale->receipt_path),
                    );

                    Storage::disk('receipts')->delete($sale->receipt_path);
                }

                DB::table('sales')
                    ->where('id', $sale->id)
                    ->update(['receipt_path' => $oldPath]);
            });
    }
};
