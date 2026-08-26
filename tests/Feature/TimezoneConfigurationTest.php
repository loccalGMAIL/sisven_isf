<?php

use App\Models\Sale;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    config()->set('app.display_timezone', 'America/Argentina/Buenos_Aires');
    FilamentTimezone::set(config('app.display_timezone'));
});

it('persists timestamps in UTC', function (): void {
    expect(config('app.timezone'))->toBe('UTC');
});

it('uses the display timezone as Filament default', function (): void {
    expect(FilamentTimezone::get())->toBe('America/Argentina/Buenos_Aires');
});

it('renders date-time columns in the display timezone', function (): void {
    $column = TextColumn::make('created_at')->dateTime();

    expect($column->getTimezone())->toBe('America/Argentina/Buenos_Aires');

    $localised = Carbon::parse('2026-08-26 02:00:00', 'UTC')
        ->setTimezone($column->getTimezone());

    expect($localised->format('d/m/Y H:i'))->toBe('25/08/2026 23:00');
});

it('leaves date-only columns unshifted', function (): void {
    expect(TextColumn::make('date')->date()->getTimezone())->toBe('UTC');
});

it('records a sale on the local calendar day', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-27 02:00:00', 'UTC'));

    $date = now(config('app.display_timezone'))->toDateString();

    expect($date)->toBe('2026-08-26')
        ->and((new Sale(['date' => $date]))->date->toDateString())->toBe('2026-08-26');

    Carbon::setTestNow();
});
