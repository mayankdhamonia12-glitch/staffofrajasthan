<?php

namespace App\Services;

use App\Models\PackageEntitlement;
use App\Models\PackagePayment;
use App\Models\PackagePurchase;
use App\Models\User;
use App\PackagePaymentStatus;
use App\PackagePurchaseStatus;
use App\PackageType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PackageSettlementService
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    public function settle(PackagePayment $payment, string $providerPaymentId): PackageEntitlement
    {
        return DB::transaction(function () use ($payment, $providerPaymentId): PackageEntitlement {
            $lockedPayment = PackagePayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $purchase = PackagePurchase::query()->whereKey($lockedPayment->package_purchase_id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === PackagePurchaseStatus::Refunded) {
                throw new RuntimeException('A refunded purchase cannot receive an entitlement.');
            }

            if ($purchase->status === PackagePurchaseStatus::Paid) {
                if ($lockedPayment->provider_payment_id === $providerPaymentId) {
                    return $purchase->entitlement()->firstOrFail();
                }

                throw new RuntimeException('A different payment already settled this purchase.');
            }

            if ($lockedPayment->provider_order_id === null || $providerPaymentId === '') {
                throw new RuntimeException('The payment has no trusted provider order reference.');
            }
            if ($lockedPayment->amount_paise !== $purchase->amount_paise || $lockedPayment->currency !== $purchase->currency) {
                throw new RuntimeException('The payment amount or currency does not match the purchase snapshot.');
            }

            $snapshot = $purchase->package_snapshot;
            $type = PackageType::tryFrom((string) ($snapshot['type'] ?? ''));
            if (! $type instanceof PackageType) {
                throw new RuntimeException('The purchase package snapshot is invalid.');
            }

            $credits = $type === PackageType::JobPosting
                ? (int) ($snapshot['job_posting_credits'] ?? 0)
                : (int) ($snapshot['candidate_access_credits'] ?? 0);
            if ($credits < 1 || $purchase->buyer_user_id === null) {
                throw new RuntimeException('The purchase cannot grant a valid entitlement.');
            }

            $validUntil = $type === PackageType::CandidateAccess
                ? now()->addDays(max(1, (int) ($snapshot['entitlement_validity_days'] ?? 0)))
                : null;

            $lockedPayment->update([
                'provider_payment_id' => $providerPaymentId,
                'status' => PackagePaymentStatus::Captured,
                'failure_code' => null,
                'captured_at' => now(),
            ]);
            $purchase->update(['status' => PackagePurchaseStatus::Paid, 'paid_at' => now()]);

            $entitlement = $purchase->entitlement()->firstOrCreate([], [
                'user_id' => $purchase->buyer_user_id,
                'package_id' => $purchase->package_id,
                'type' => $type,
                'credits_granted' => $credits,
                'credits_remaining' => $credits,
                'package_snapshot' => $snapshot,
                'starts_at' => now(),
                'valid_until' => $validUntil,
            ]);

            $buyer = $purchase->buyer;
            if ($buyer instanceof User) {
                $this->audit->record(
                    $buyer,
                    'package.purchase_paid',
                    $purchase,
                    'Verified payment settled for purchase '.$purchase->purchase_reference.'.',
                    null,
                    null,
                    ['amount_paise' => $purchase->amount_paise, 'currency' => $purchase->currency, 'entitlement_id' => $entitlement->getKey()],
                );
            }

            return $entitlement;
        });
    }

    public function fail(PackagePayment $payment, ?string $failureCode = null): void
    {
        DB::transaction(function () use ($payment, $failureCode): void {
            $lockedPayment = PackagePayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $purchase = PackagePurchase::query()->whereKey($lockedPayment->package_purchase_id)->lockForUpdate()->firstOrFail();

            if ($purchase->status === PackagePurchaseStatus::Paid || $lockedPayment->status === PackagePaymentStatus::Captured) {
                return;
            }

            $lockedPayment->update([
                'status' => PackagePaymentStatus::Failed,
                'failure_code' => $failureCode ?: 'payment_failed',
            ]);
            if ($purchase->status !== PackagePurchaseStatus::Refunded) {
                $purchase->update(['status' => PackagePurchaseStatus::Failed]);
            }
        });
    }
}
