<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class RazorpayGateway
{
    public function paymentsReady(): bool
    {
        $keyId = trim((string) config('services.razorpay.key_id'));
        $keySecret = trim((string) config('services.razorpay.key_secret'));
        $webhookSecret = trim((string) config('services.razorpay.webhook_secret'));
        $mode = (string) config('services.razorpay.mode', 'test');

        if ($keyId === '' || $keySecret === '' || $webhookSecret === '') {
            return false;
        }

        return match ($mode) {
            'test' => str_starts_with($keyId, 'rzp_test_'),
            'live' => str_starts_with($keyId, 'rzp_live_'),
            default => false,
        };
    }

    public function webhooksReady(): bool
    {
        return trim((string) config('services.razorpay.webhook_secret')) !== '';
    }

    public function publicKeyId(): ?string
    {
        return $this->paymentsReady() ? (string) config('services.razorpay.key_id') : null;
    }

    /** @return array<string, mixed> */
    public function createOrder(int $amountPaise, string $currency, string $receipt): array
    {
        $response = $this->post('/orders', [
            'amount' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'partial_payment' => false,
        ]);

        if (! $response->successful()) {
            throw new RazorpayGatewayException('order_creation_failed');
        }

        $order = $response->json();
        if (! is_array($order)
            || ! is_string($order['id'] ?? null)
            || ! str_starts_with($order['id'], 'order_')
            || (int) ($order['amount'] ?? 0) !== $amountPaise
            || ($order['currency'] ?? null) !== $currency) {
            throw new RazorpayGatewayException('invalid_order_response');
        }

        return $order;
    }

    /** @return array<string, mixed> */
    public function fetchPayment(string $paymentId): array
    {
        $response = $this->get('/payments/'.rawurlencode($paymentId));

        if (! $response->successful()) {
            throw new RazorpayGatewayException('payment_fetch_failed');
        }

        $payment = $response->json();
        if (! is_array($payment)) {
            throw new RazorpayGatewayException('invalid_payment_response');
        }

        return $payment;
    }

    public function verifyCheckoutSignature(string $trustedOrderId, string $paymentId, string $signature): bool
    {
        $secret = (string) config('services.razorpay.key_secret');
        if ($secret === '' || $trustedOrderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $trustedOrderId.'|'.$paymentId, $secret), $signature);
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    private function request(): PendingRequest
    {
        if (! $this->paymentsReady()) {
            throw new RazorpayGatewayException('payment_configuration_incomplete');
        }

        return Http::baseUrl((string) config('services.razorpay.base_url'))
            ->withBasicAuth((string) config('services.razorpay.key_id'), (string) config('services.razorpay.key_secret'))
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    /** @param array<string, int|string|bool> $payload */
    private function post(string $path, array $payload): Response
    {
        try {
            return $this->request()->post($path, $payload);
        } catch (ConnectionException) {
            throw new RazorpayGatewayException('provider_connection_failed');
        } catch (RazorpayGatewayException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new RazorpayGatewayException('provider_request_failed');
        }
    }

    private function get(string $path): Response
    {
        try {
            return $this->request()->get($path);
        } catch (ConnectionException) {
            throw new RazorpayGatewayException('provider_connection_failed');
        } catch (RazorpayGatewayException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new RazorpayGatewayException('provider_request_failed');
        }
    }
}
