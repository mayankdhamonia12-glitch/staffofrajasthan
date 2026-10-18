# Paid packages and Razorpay setup

This milestone adds one-time INR payments for job-posting credits and candidate-contact access. It does not add recurring subscriptions, refunds, franchise payments, or Prime Evolve services.

## Safe defaults

Package purchases and paid job-posting enforcement are both disabled by default in `platform_settings`. The platform owner controls those switches in Admin → Platform settings. Razorpay secrets are never stored in the database or shown in the admin UI. Do not enable package sales until credentials and a webhook have been configured and verified.

Set these server environment variables:

```dotenv
RAZORPAY_MODE=test
RAZORPAY_KEY_ID=rzp_test_...
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...
```

Test mode requires a `rzp_test_` key ID. Live mode requires an explicit `RAZORPAY_MODE=live` and an `rzp_live_` key ID. The application will not expose a checkout key or create orders unless the mode and key prefix match and all three credentials are present. Restart/reload the application after changing environment configuration.

In the Razorpay dashboard, configure the webhook URL as `https://<your-domain>/webhooks/razorpay` and subscribe to `payment.captured`, `order.paid`, and `payment.failed`. Copy the webhook signing secret into `RAZORPAY_WEBHOOK_SECRET`. This endpoint verifies the signature over the exact raw request body before JSON parsing; configure your proxy to preserve the body unchanged. Event IDs are stored for deduplication. A temporary processing failure returns a retryable error to Razorpay.

## Payment and entitlement flow

1. An employer selects an active package. The server snapshots its name, type, INR amount in paise, credits, validity, and features.
2. The server creates the Razorpay order. Browser-provided amount or package data is never used to price the order.
3. Razorpay Checkout returns an order/payment/signature tuple. The server verifies the HMAC using its trusted order ID, then fetches the payment from Razorpay and checks captured status, order ID, integer amount, and currency.
4. The signed webhook independently verifies the raw-body HMAC and deduplicates its provider event ID. Only a captured payment matching a stored order and purchase can settle.
5. Settlement is transactional and idempotent. A unique purchase-to-entitlement constraint prevents double grants. Failed, pending, mismatched, or unauthenticated payments grant no benefit.

The browser checkout callback alone never marks a purchase paid. If the provider API is temporarily unavailable, leave the purchase pending and retry verification later. Package purchase references and purchase snapshots remain stable if the catalog is edited.

## Employer packages

The Admin/Owner Packages screen manages the catalog. Deactivate packages rather than deleting them so historical purchase references remain intact. Package purchases are one-time; no recurring billing is implemented. Admins can inspect payment attempts but cannot manually mark a purchase paid.

Job posting is free while the owner-controlled paid-posting setting is off. When enabled, a job-posting credit is consumed only on first publication. Closing, editing, filling, or reopening a paid listing does not consume another credit. Paid listings stop appearing publicly at their stored expiry; expired listings cannot be reopened. Existing free listings are not retroactively charged.

Candidate access credits are consumed atomically on first unlock per employer/candidate pair. Repeat viewing during the access window does not consume another credit. Access requires an active employer with an approved company, a public candidate profile, and the candidate's separate opt-in for each private field. Contact information and resumes remain private on the public directory. Resume files are streamed from the configured private candidate disk after authorization.

## Testing and launch checklist

- Automated tests use `Http::fake()` and deterministic HMACs; they do not call Razorpay or simulate real/test card charges.
- Use Razorpay test credentials and dashboard test events to validate order creation, checkout callbacks, and webhook delivery before considering live credentials.
- Verify the webhook URL is reachable over HTTPS and returns a successful response for valid signed test events.
- Confirm package purchases first, while paid job-posting enforcement remains off; only enable enforcement after the catalog and payment flow are operational.
- Live processing requires deliberate production environment configuration and a live-mode key prefix. Keep credentials in the deployment secret store, not source control.

Razorpay API and webhook details: [Standard Checkout integration](https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/integration-steps/), [webhook signature validation](https://razorpay.com/docs/webhooks/validate-test/), and [webhooks overview](https://razorpay.com/docs/webhooks/).
