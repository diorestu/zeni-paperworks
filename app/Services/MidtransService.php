<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function createSnapTransaction(array $payload): array
    {
        return $this->request()
            ->post($this->snapBaseUrl().'/transactions', $payload)
            ->throw()
            ->json();
    }

    public function getTransactionStatus(string $orderId): array
    {
        return $this->request()
            ->get($this->apiBaseUrl().'/'.$orderId.'/status')
            ->throw()
            ->json();
    }

    public function verifyNotificationSignature(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $required = ['order_id', 'status_code', 'gross_amount', 'signature_key'];
        foreach ($required as $field) {
            if (! isset($payload[$field])) {
                return false;
            }
        }

        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$serverKey);

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    private function request(): PendingRequest
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('Midtrans server key is not configured.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withBasicAuth($serverKey, '')
            ->timeout(15);
    }

    private function snapBaseUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    private function apiBaseUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    private function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production', false);
    }
}
