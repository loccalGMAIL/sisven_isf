<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(ShieldSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('super_admin');

        $products = Product::factory(10)->create();

        Sale::factory(5)
            ->for($user)
            ->create()
            ->each(function (Sale $sale) use ($products): void {
                $details = $products->random(random_int(1, 4))
                    ->map(fn (Product $product) => $sale->saleDetails()->create([
                        'product_id' => $product->id,
                        'quantity' => random_int(1, 5),
                        'price' => $product->price,
                    ]));

                $sale->update([
                    'total' => $details->sum(fn ($detail) => $detail->quantity * $detail->price),
                ]);
            });
    }
}
