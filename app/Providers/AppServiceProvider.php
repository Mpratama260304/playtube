<?php

namespace App\Providers;

use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fallback for Number::format when intl extension is not available
        // This prevents Filament admin panel from crashing
        if (!extension_loaded('intl')) {
            Number::macro('format', function ($number, ?int $precision = null, ?int $maxPrecision = null, ?string $locale = null) {
                if (is_null($number)) {
                    return null;
                }
                
                $precision = $precision ?? 0;
                return number_format((float) $number, $precision, '.', ',');
            });
            
            Number::macro('percentage', function ($number, ?int $precision = 0, ?int $maxPrecision = null, ?string $locale = null) {
                if (is_null($number)) {
                    return null;
                }
                
                return number_format((float) $number, $precision, '.', ',') . '%';
            });
            
            Number::macro('currency', function ($number, ?string $in = 'USD', ?string $locale = null) {
                if (is_null($number)) {
                    return null;
                }
                
                $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'IDR' => 'Rp'];
                $symbol = $symbols[$in] ?? $in . ' ';
                return $symbol . number_format((float) $number, 2, '.', ',');
            });
            
            Number::macro('abbreviate', function ($number, ?int $precision = 0, ?int $maxPrecision = null) {
                if (is_null($number)) {
                    return null;
                }
                
                $number = (float) $number;
                $abbrevs = [
                    12 => 'T',
                    9 => 'B', 
                    6 => 'M',
                    3 => 'K',
                    0 => ''
                ];
                
                foreach ($abbrevs as $exp => $suffix) {
                    if (abs($number) >= pow(10, $exp)) {
                        return number_format($number / pow(10, $exp), $precision) . $suffix;
                    }
                }
                
                return (string) $number;
            });
        }
    }
}
