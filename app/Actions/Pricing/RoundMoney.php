<?php

namespace App\Actions\Pricing;

class RoundMoney
{
    public function handle(float $value): float
    {
        $precision = (int) config('pricing.rounding.precision', 2);
        $mode = (string) config('pricing.rounding.mode', 'round');

        return match ($mode) {
            'ceil' => $this->ceil($value, $precision),
            'floor' => $this->floor($value, $precision),
            default => round($value, $precision),
        };
    }

    private function ceil(float $value, int $precision): float
    {
        $factor = 10 ** $precision;

        return ceil($value * $factor) / $factor;
    }

    private function floor(float $value, int $precision): float
    {
        $factor = 10 ** $precision;

        return floor($value * $factor) / $factor;
    }
}
