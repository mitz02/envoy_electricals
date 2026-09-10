<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Paystack REST API using Laravel's HTTP client.
 * All amounts are converted to kobo (smallest currency unit) as Paystack requires.
 */
class PaystackService
{
    protected string $secretKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('services.paystack.secret_key');
        $this->baseUrl = rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/');
    }

    public function isConfigured(): bool
    {
        return $this->secretKey !== '';
    }

    /**
     * Initialize a transaction and return a payment link (authorization_url).
     *
     * @return array decoded JSON response from Paystack
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function initialize(
        string $reference,
        float $amount,
        string $email,
        ?string $callbackUrl = null,
        array $metadata = []
    ): array {
        $response = $this->client()->post('/transaction/initialize', [
            'reference' => $reference,
            'amount' => $this->toKobo($amount),
            'currency' => 'NGN',
            'email' => $email,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata ?: new \stdClass(),
            'channels' => ['card', 'bank', 'ussd', 'bank_transfer'],
        ]);

        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * Verify a transaction server-side (never trust the customer's browser).
     *
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function verify(string $reference): array
    {
        $response = $this->client()->get("/transaction/verify/{$reference}");
        $response->throw();

        return $response->json() ?? [];
    }

    public function isSuccessfulVerification(array $verification): bool
    {
        return ($verification['status'] ?? false) === true
            && ($verification['data']['status'] ?? null) === 'success'
            && ($verification['data']['paid_at'] ?? null) !== null;
    }

    /**
     * Validate the Paystack webhook HMAC signature. Returns false when the
     * service is not configured so a misconfigured webhook never passes.
     */
    public function signatureIsValid(Request $request): bool
    {
        $signature = (string) $request->header('x-paystack-signature');

        if ($signature === '' || ! $this->isConfigured()) {
            Log::warning('Paystack webhook rejected: missing signature or unconfigured service.');

            return false;
        }

        $computed = hash_hmac('sha512', (string) $request->getContent(), $this->secretKey);

        return hash_equals($computed, $signature);
    }

    protected function toKobo(float $amount): int
    {
        return (int) round($amount * 100);
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return \Illuminate\Support\Facades\Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken($this->secretKey, 'Bearer')
            ->withOptions(['timeout' => 30]);
    }
}