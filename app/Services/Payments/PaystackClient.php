<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PaystackClient
{
    private function baseUrl(): string
    {
        return (string) config('services.paystack.base_url', 'https://api.paystack.co');
    }

    private function secretKey(): string
    {
        return (string) config('services.paystack.secret_key');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function initialize(array $payload): Response
    {
        return Http::withToken($this->secretKey())
            ->post($this->baseUrl().'/transaction/initialize', $payload);
    }

    public function verify(string $reference): Response
    {
        return Http::withToken($this->secretKey())
            ->get($this->baseUrl().'/transaction/verify/'.$reference);
    }
}
