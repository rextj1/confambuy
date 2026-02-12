<?php

namespace App\Actions\Pricing;

use App\Models\Address;
use App\Services\Shipping\CarrierRateProvider;

class CalculateShipping
{
    /**
     * @return array{amount:float, carrier:string, label:?string, zone:?string}
     */
    public function forAddress(float $weight, ?Address $address, ?string $carrier): array
    {
        $zones = (array) config('pricing.shipping.zones', []);
        $defaultCarrier = (string) config('pricing.shipping.default_carrier', 'standard');
        $selectedCarrier = $carrier ?: $defaultCarrier;

        $zone = $this->matchZone($zones, $address);

        if (! $zone) {
            return [
                'amount' => 0.0,
                'carrier' => $selectedCarrier,
                'label' => null,
                'zone' => null,
            ];
        }

        $carrierConfig = $zone['carriers'][$selectedCarrier] ?? $zone['carriers'][$defaultCarrier] ?? null;

        if (! $carrierConfig) {
            $provider = app(CarrierRateProvider::class);

            return $provider->getRate($weight, $address, $selectedCarrier);
        }

        if (! empty($carrierConfig['external'])) {
            return [
                'amount' => 0.0,
                'carrier' => $selectedCarrier,
                'label' => null,
                'zone' => $zone['name'] ?? null,
            ];
        }

        $rate = $this->resolveRate($carrierConfig['rates'] ?? [], $weight);

        return [
            'amount' => $rate,
            'carrier' => $selectedCarrier,
            'label' => $carrierConfig['label'] ?? $selectedCarrier,
            'zone' => $zone['name'] ?? null,
        ];
    }

    /**
     * @param  array<int, array{max_weight: float|int, price: float|int}>  $rates
     */
    private function resolveRate(array $rates, float $weight): float
    {
        foreach ($rates as $rate) {
            if ($weight <= (float) $rate['max_weight']) {
                return (float) $rate['price'];
            }
        }

        $last = end($rates);

        return $last ? (float) $last['price'] : 0.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $zones
     */
    private function matchZone(array $zones, ?Address $address): ?array
    {
        if (! $address) {
            return null;
        }

        $country = $address->country;
        $state = $address->state;

        foreach ($zones as $zone) {
            $countries = $zone['countries'] ?? [];
            $states = $zone['states'] ?? [];

            $countryMatch = empty($countries) || in_array($country, $countries, true);
            $stateMatch = empty($states) || in_array($state, $states, true);

            if ($countryMatch && $stateMatch) {
                return $zone;
            }
        }

        return null;
    }
}
