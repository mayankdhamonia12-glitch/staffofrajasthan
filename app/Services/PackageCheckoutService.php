<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\PackagePurchase;
use App\Models\User;
use App\PackagePaymentStatus;
use App\PackagePurchaseStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageCheckoutService
{
    public function __construct(private readonly RazorpayGateway $gateway) {}

    public function start(Package $package, User $buyer, string $idempotencyKey): PackagePurchase
    {
        return DB::transaction(function () use ($package, $buyer, $idempotencyKey): PackagePurchase {
            $snapshot = [
                'type' => $package->type->value,
                'name' => $package->name,
                'description' => $package->description,
                'price_paise' => $package->price_paise,
                'currency' => 'INR',
                'job_posting_credits' => $package->job_posting_credits,
                'listing_validity_days' => $package->listing_validity_days,
                'candidate_access_credits' => $package->candidate_access_credits,
                'entitlement_validity_days' => $package->entitlement_validity_days,
                'features' => $package->features ?? [],
            ];

            $purchase = PackagePurchase::query()->firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'purchase_reference' => (string) str()->uuid(),
                    'buyer_user_id' => $buyer->getKey(),
                    'package_id' => $package->getKey(),
                    'package_snapshot' => $snapshot,
                    'amount_paise' => $package->price_paise,
                    'currency' => 'INR',
                    'status' => PackagePurchaseStatus::Pending,
                ],
            );

            $purchase = PackagePurchase::query()->whereKey($purchase->getKey())->lockForUpdate()->firstOrFail();
            abort_unless((int) $purchase->buyer_user_id === (int) $buyer->getKey() && (int) $purchase->package_id === (int) $package->getKey(), 409);

            if ($purchase->status !== PackagePurchaseStatus::Pending) {
                return $purchase;
            }

            $existing = $purchase->payments()
                ->where('status', PackagePaymentStatus::Created->value)
                ->whereNotNull('provider_order_id')
                ->latest('id')
                ->first();

            if ($existing instanceof PackagePayment) {
                return $purchase;
            }

            return $this->createAttempt($purchase);
        });
    }

    public function retry(PackagePurchase $purchase, User $buyer): PackagePurchase
    {
        return DB::transaction(function () use ($purchase, $buyer): PackagePurchase {
            $purchase = PackagePurchase::query()->whereKey($purchase->getKey())->lockForUpdate()->firstOrFail();
            abort_unless((int) $purchase->buyer_user_id === (int) $buyer->getKey(), 404);
            abort_unless(in_array($purchase->status, [PackagePurchaseStatus::Pending, PackagePurchaseStatus::Failed], true), 409);

            $existing = $purchase->payments()
                ->where('status', PackagePaymentStatus::Created->value)
                ->whereNotNull('provider_order_id')
                ->latest('id')
                ->first();
            if ($existing instanceof PackagePayment) {
                return $purchase->load('payments');
            }

            $purchase->update(['status' => PackagePurchaseStatus::Pending]);

            return $this->createAttempt($purchase);
        });
    }

    private function createAttempt(PackagePurchase $purchase): PackagePurchase
    {
        $payment = $purchase->payments()->create([
            'provider' => 'razorpay',
            'amount_paise' => $purchase->amount_paise,
            'currency' => $purchase->currency,
            'status' => PackagePaymentStatus::Created,
        ]);

        try {
            $order = $this->gateway->createOrder(
                $purchase->amount_paise,
                $purchase->currency,
                str_replace('-', '', $purchase->purchase_reference),
            );
            $payment->update(['provider_order_id' => $order['id']]);
        } catch (RazorpayGatewayException $exception) {
            $payment->update(['status' => PackagePaymentStatus::Failed, 'failure_code' => $exception->failureCode]);
            Log::warning('Razorpay order creation failed.', [
                'purchase_reference' => $purchase->purchase_reference,
                'failure_code' => $exception->failureCode,
            ]);
        }

        return $purchase->fresh(['payments']) ?? $purchase;
    }
}
