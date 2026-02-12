<?php

namespace App\Services\Tax;

use App\Models\Address;

class TaxProvider
{
    public function getRate(Address $address): float
    {
        return 0.0;
    }
}
