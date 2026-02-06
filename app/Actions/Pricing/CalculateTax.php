<?php

namespace App\Actions\Pricing;

use App\Models\Address;
use App\Services\Tax\TaxProvider;

class CalculateTax
{
    /**
     * @return array{rate:float, amount:float, region:string}
     */
    public function forAddress(float $taxable, ?Address $address): array
    {
        $rate = $this->resolveRate($address);

        return [
            'rate' => $rate,
            'amount' => $taxable * $rate,
            'region' => $this->resolveRegion($address),
        ];
    }

    private function resolveRate(?Address $address): float
    {
        if ($address) {
            $provider = app(TaxProvider::class);
            $providerRate = $provider->getRate($address);

            if ($providerRate > 0) {
                return $providerRate;
            }
        }

        $zones = (array) config('pricing.tax_zones', []);

        if (! $address) {
            return (float) ($zones['default'] ?? 0.0);
        }

        $country = (string) $address->country;
        $state = (string) $address->state;

        if (isset($zones[$country])) {
            $zone = $zones[$country];
            $stateRates = $zone['states'] ?? [];

            if ($state && isset($stateRates[$state])) {
                return (float) $stateRates[$state];
            }

            return (float) ($zone['default'] ?? 0.0);
        }

        return (float) ($zones['default'] ?? 0.0);
    }

    private function resolveRegion(?Address $address): string
    {
        if (! $address) {
            return 'default';
        }

        $country = $address->country ?: 'default';
        $state = $address->state ?: 'default';

        return trim($country.'-'.$state, '-');
    }
}
