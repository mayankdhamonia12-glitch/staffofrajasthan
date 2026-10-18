<?php

namespace App\Http\Controllers;

use App\Models\PackagePayment;
use App\Models\PackagePurchase;
use App\Models\PaymentWebhookEvent;
use App\PackagePurchaseStatus;
use App\PaymentWebhookStatus;
use App\Services\PackageSettlementService;
use App\Services\RazorpayGateway;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class RazorpayWebhookController extends Controller
{
    public function __invoke(Request $request, RazorpayGateway $gateway, PackageSettlementService $settlements): JsonResponse
    {
        if (! $gateway->webhooksReady()) {
            return response()->json(['accepted' => false], 503);
        }

        $rawBody = $request->getContent();
        $signatureHeader = $request->headers->get('X-Razorpay-Signature');
        $signature = is_string($signatureHeader) ? $signatureHeader : '';
        if (! $gateway->verifyWebhookSignature($rawBody, $signature)) {
            return response()->json(['accepted' => false], 401);
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['accepted' => false], 400);
        }

        if (! is_array($payload) || ! is_string($payload['event'] ?? null)) {
            return response()->json(['accepted' => false], 400);
        }

        $eventIdHeader = $request->headers->get('X-Razorpay-Event-Id');
        $payloadEventId = $payload['id'] ?? null;
        $eventId = $eventIdHeader ?? (is_string($payloadEventId) ? $payloadEventId : hash('sha256', $rawBody));
        if (! preg_match('/^[A-Za-z0-9_-]{1,120}$/', $eventId)) {
            return response()->json(['accepted' => false], 400);
        }

        $event = $this->eventRecord($eventId, $payload['event'], hash('sha256', $rawBody));
        if ($event === null) {
            return response()->json(['accepted' => false], 409);
        }
        if (in_array($event->status, [PaymentWebhookStatus::Processed, PaymentWebhookStatus::Ignored], true)) {
            return response()->json(['accepted' => true]);
        }
        if ($event->status === PaymentWebhookStatus::Processing) {
            return response()->json(['accepted' => false], 503);
        }

        $event->update(['status' => PaymentWebhookStatus::Processing, 'failure_code' => null]);

        try {
            $result = DB::transaction(function () use ($payload, $event, $settlements): PaymentWebhookStatus {
                $eventName = (string) $payload['event'];
                if (! in_array($eventName, ['payment.captured', 'order.paid', 'payment.failed'], true)) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                $paymentEntity = data_get($payload, 'payload.payment.entity');
                $orderEntity = data_get($payload, 'payload.order.entity');
                if (! is_array($paymentEntity)) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                $providerOrderId = $paymentEntity['order_id'] ?? (is_array($orderEntity) ? ($orderEntity['id'] ?? null) : null);
                if (! is_string($providerOrderId) || $providerOrderId === '') {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }
                if (is_array($orderEntity) && isset($orderEntity['id']) && $orderEntity['id'] !== $providerOrderId) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                $payment = PackagePayment::query()->where('provider_order_id', $providerOrderId)->lockForUpdate()->first();
                if (! $payment instanceof PackagePayment) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                $purchase = PackagePurchase::query()->whereKey($payment->package_purchase_id)->lockForUpdate()->firstOrFail();
                if ((int) ($paymentEntity['amount'] ?? -1) !== $payment->amount_paise
                    || ($paymentEntity['currency'] ?? null) !== $payment->currency
                    || $payment->amount_paise !== $purchase->amount_paise
                    || $payment->currency !== $purchase->currency) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                if ($eventName === 'payment.failed') {
                    if (($paymentEntity['status'] ?? null) !== 'failed' || (bool) ($paymentEntity['captured'] ?? false)) {
                        $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                        return PaymentWebhookStatus::Ignored;
                    }
                    $settlements->fail($payment, 'payment_failed');
                    $event->update(['status' => PaymentWebhookStatus::Processed, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Processed;
                }

                $paymentId = $paymentEntity['id'] ?? null;
                $captured = ($paymentEntity['status'] ?? null) === 'captured' && (bool) ($paymentEntity['captured'] ?? false);
                $orderPaid = $eventName !== 'order.paid' || (is_array($orderEntity) && ($orderEntity['status'] ?? null) === 'paid');
                if (! is_string($paymentId) || ! str_starts_with($paymentId, 'pay_') || ! $captured || ! $orderPaid) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                if ($purchase->status === PackagePurchaseStatus::Paid && $payment->provider_payment_id !== $paymentId) {
                    $event->update(['status' => PaymentWebhookStatus::Ignored, 'processed_at' => now()]);

                    return PaymentWebhookStatus::Ignored;
                }

                $settlements->settle($payment, $paymentId);
                $event->update(['status' => PaymentWebhookStatus::Processed, 'processed_at' => now()]);

                return PaymentWebhookStatus::Processed;
            });

            return response()->json(['accepted' => true, 'result' => $result->value]);
        } catch (Throwable) {
            PaymentWebhookEvent::query()->whereKey($event->getKey())->update([
                'status' => PaymentWebhookStatus::Failed->value,
                'failure_code' => 'event_processing_failed',
            ]);
            Log::error('Razorpay webhook processing failed.', ['event_id' => $eventId, 'event_type' => $event->event_type]);

            return response()->json(['accepted' => false], 500);
        }
    }

    private function eventRecord(string $eventId, string $eventType, string $payloadHash): ?PaymentWebhookEvent
    {
        try {
            $event = PaymentWebhookEvent::query()->firstOrCreate(
                ['event_id' => $eventId],
                [
                    'event_type' => Str::limit($eventType, 80, ''),
                    'payload_hash' => $payloadHash,
                    // A failed marker lets a newly inserted event enter processing below;
                    // concurrent duplicate deliveries see Processing and receive a retryable 503.
                    'status' => PaymentWebhookStatus::Failed,
                    'received_at' => now(),
                ],
            );
        } catch (UniqueConstraintViolationException) {
            $event = PaymentWebhookEvent::query()->where('event_id', $eventId)->first();
        }

        if (! $event instanceof PaymentWebhookEvent || ! hash_equals($event->payload_hash, $payloadHash)) {
            return null;
        }

        return $event;
    }
}
