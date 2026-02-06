<?php

namespace App\Services\Shipping;

use App\Models\Address;

class CarrierRateProvider
{
    /**
     * @return array{amount:float, carrier:string, label:?string}
     */
    public function getRate(float $weight, Address $address, string $carrier): array
    {
        return [
            'amount' => 0.0,
            'carrier' => $carrier,
            'label' => $carrier,
        ];
    }
}
