<?php

namespace App\Contracts\Payments;

interface PaymentGateway
{
    /**
     * Canonical provider identifier (e.g. 'zarinpal', 'idpay').
     *
     * This value is never derived from user input; the gateway is selected
     * exclusively through the server-side allowlist in PaymentGatewayManager.
     */
    public function name(): string;

    /**
     * Prepare a payment for online processing and return the information needed
     * to redirect the customer (redirect URL + provider reference).
     *
     * The amount and order context are provided by the Payment Core through
     * $request and must never be read from the browser/request by an
     * implementation.
     *
     * Implementations must not mutate Payment/Order records; they only report
     * the outcome of the interaction with the provider. Out-of-band failures
     * are surfaced as a normalized PaymentInitiationResult, not provider SDK
     * exceptions.
     */
    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult;

    /**
     * Verify a payment after the provider callback.
     *
     * $request carries the trusted order context known when the payment was
     * initiated. $providerReference is the reference previously returned by
     * initiate(). $callbackData is the raw provider callback payload.
     *
     * As with initiate(), this method only reports the provider result.
     * Applying the result to Payment/Order state is the responsibility of the
     * Payment Core.
     */
    public function verify(PaymentInitiationRequest $request, string $providerReference, array $callbackData): PaymentVerificationResult;
}
