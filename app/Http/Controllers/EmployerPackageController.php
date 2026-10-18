<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageEntitlement;
use App\Models\PackagePurchase;
use App\PackagePaymentStatus;
use App\PackagePurchaseStatus;
use App\Services\PackageCheckoutService;
use App\Services\PackageSettlementService;
use App\Services\PlatformSettingsService;
use App\Services\RazorpayGateway;
use App\Services\RazorpayGatewayException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployerPackageController extends Controller
{
    public function index(PlatformSettingsService $settings, RazorpayGateway $gateway): View
    {
        $packages = Package::query()->active()->orderBy('display_order')->orderBy('id')->get();
        $entitlements = PackageEntitlement::query()
            ->where('user_id', auth()->id())
            ->where('credits_remaining', '>', 0)
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->orderBy('created_at')
            ->get();

        return view('employer.packages.index', [
            'packages' => $packages,
            'entitlements' => $entitlements,
            'purchasesEnabled' => $settings->current()->package_purchases_enabled && $gateway->paymentsReady(),
            'paymentSetupReady' => $gateway->paymentsReady(),
        ]);
    }

    public function review(Package $package): View
    {
        abort_unless($package->is_active, 404);

        return view('employer.packages.review', ['package' => $package, 'idempotencyKey' => (string) Str::uuid()]);
    }

    public function checkout(
        Request $request,
        Package $package,
        PlatformSettingsService $settings,
        RazorpayGateway $gateway,
        PackageCheckoutService $checkout,
    ): RedirectResponse {
        abort_unless($package->is_active, 404);
        if (! $settings->current()->package_purchases_enabled) {
            throw ValidationException::withMessages(['checkout' => 'Package purchases are currently disabled by the platform owner.']);
        }
        if (! $gateway->paymentsReady()) {
            throw ValidationException::withMessages(['checkout' => 'Payment setup is incomplete. Ask the platform owner to configure Razorpay credentials for the selected mode and the webhook secret.']);
        }

        $data = $request->validate(['idempotency_key' => ['required', 'uuid']]);
        $purchase = $checkout->start($package, $request->user(), $data['idempotency_key']);
        $status = $purchase->payments()->latest('id')->first()?->failure_code
            ? 'The payment provider is temporarily unavailable. Your purchase was not marked paid; you can retry safely.'
            : null;

        return redirect()->route('employer.purchases.show', $purchase)->with('status', $status);
    }

    public function history(Request $request): View
    {
        $purchases = PackagePurchase::query()
            ->with(['payments', 'entitlement'])
            ->where('buyer_user_id', $request->user()->getKey())
            ->latest('created_at')
            ->paginate(15);

        return view('employer.purchases.index', compact('purchases'));
    }

    public function show(Request $request, PackagePurchase $purchase, PlatformSettingsService $settings, RazorpayGateway $gateway): View
    {
        $purchase = $this->ownedPurchase($request, $purchase)->load(['payments' => fn ($query) => $query->latest('id'), 'entitlement']);
        $payment = $purchase->payments->first();

        return view('employer.purchases.show', [
            'purchase' => $purchase,
            'payment' => $payment,
            'checkoutReady' => $settings->current()->package_purchases_enabled && $gateway->paymentsReady(),
            'razorpayKeyId' => $gateway->publicKeyId(),
        ]);
    }

    public function verify(
        Request $request,
        PackagePurchase $purchase,
        RazorpayGateway $gateway,
        PackageSettlementService $settlements,
    ): RedirectResponse {
        $purchase = $this->ownedPurchase($request, $purchase);
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:80', 'regex:/^order_[A-Za-z0-9]+$/'],
            'razorpay_payment_id' => ['required', 'string', 'max:80', 'regex:/^pay_[A-Za-z0-9]+$/'],
            'razorpay_signature' => ['required', 'string', 'size:64', 'regex:/^[a-fA-F0-9]+$/'],
        ]);

        $payment = $purchase->payments()
            ->where('provider_order_id', $data['razorpay_order_id'])
            ->whereIn('status', [PackagePaymentStatus::Created->value, PackagePaymentStatus::Captured->value])
            ->firstOrFail();

        if (! $gateway->verifyCheckoutSignature($payment->provider_order_id, $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            throw ValidationException::withMessages(['payment' => 'The payment signature could not be verified. No package benefit was granted.']);
        }

        try {
            $providerPayment = $gateway->fetchPayment($data['razorpay_payment_id']);
        } catch (RazorpayGatewayException $exception) {
            return back()->with('status', 'Payment verification is temporarily unavailable. Your purchase remains pending until server confirmation.');
        }

        $isCaptured = ($providerPayment['status'] ?? null) === 'captured'
            && (bool) ($providerPayment['captured'] ?? false)
            && ($providerPayment['order_id'] ?? null) === $payment->provider_order_id
            && (int) ($providerPayment['amount'] ?? -1) === $payment->amount_paise
            && ($providerPayment['currency'] ?? null) === $payment->currency
            && $payment->amount_paise === $purchase->amount_paise
            && $payment->currency === $purchase->currency;

        if (! $isCaptured) {
            return redirect()->route('employer.purchases.show', $purchase)
                ->with('status', 'Razorpay has not confirmed a captured payment matching this purchase. Its status remains pending.');
        }

        $purchase->refresh();
        if ($purchase->status === PackagePurchaseStatus::Paid && $payment->provider_payment_id !== $data['razorpay_payment_id']) {
            return redirect()->route('employer.purchases.show', $purchase)
                ->with('status', 'This purchase was already fulfilled by another captured payment. Contact support before retrying any further payments.');
        }

        $settlements->settle($payment, $data['razorpay_payment_id']);

        return redirect()->route('employer.purchases.show', $purchase)->with('status', 'Payment verified. Your package entitlement is ready.');
    }

    public function retry(
        Request $request,
        PackagePurchase $purchase,
        PlatformSettingsService $settings,
        RazorpayGateway $gateway,
        PackageCheckoutService $checkout,
    ): RedirectResponse {
        $purchase = $this->ownedPurchase($request, $purchase);
        if (! $settings->current()->package_purchases_enabled || ! $gateway->paymentsReady()) {
            throw ValidationException::withMessages(['checkout' => 'Package purchases or payment configuration are currently unavailable.']);
        }

        $purchase = $checkout->retry($purchase, $request->user());

        return redirect()->route('employer.purchases.show', $purchase)->with('status', 'A payment attempt is ready to continue. No entitlement is granted until server verification succeeds.');
    }

    private function ownedPurchase(Request $request, PackagePurchase $purchase): PackagePurchase
    {
        return PackagePurchase::query()
            ->where('buyer_user_id', $request->user()->getKey())
            ->whereKey($purchase->getKey())
            ->firstOrFail();
    }
}
