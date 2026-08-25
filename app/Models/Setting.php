<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['dollar_rate', 'dollar_rounding'])]
class Setting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dollar_rate' => 'decimal:4',
            'dollar_rounding' => 'decimal:4',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public static function usdAmountFor(float $pesos): float
    {
        $setting = static::current();

        $rate = (float) $setting->dollar_rate;

        if ($rate <= 0) {
            return 0.0;
        }

        $step = (float) $setting->dollar_rounding;

        if ($step <= 0) {
            $step = 1;
        }

        return round(($pesos / $rate) / $step) * $step;
    }
}
