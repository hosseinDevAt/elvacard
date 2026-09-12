<?php

namespace App\Services;

use App\Contracts\Payments\PaymentGateway;
use App\Exceptions\UnknownPaymentGatewayException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a canonical gateway name to a gateway implementation.
 *
 * Resolution goes exclusively through the server-side allowlist; the requested
 * name is never treated as a class name. Arbitrary user input therefore cannot
 * trigger instantiation of arbitrary classes.
 */
final class PaymentGatewayManager
{
    /**
     * @param  array<string, class-string<PaymentGateway>>  $gateways  server-side allowlist
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $gateways,
    ) {}

    public function resolve(string $name): PaymentGateway
    {
        $class = $this->gateways[$name] ?? null;

        if ($class === null) {
            throw new UnknownPaymentGatewayException("Payment gateway [{$name}] is not configured.");
        }

        $gateway = $this->container->make($class);

        if (! $gateway instanceof PaymentGateway) {
            throw new UnknownPaymentGatewayException("Payment gateway [{$name}] does not implement the gateway contract.");
        }

        if ($gateway->name() !== $name) {
            throw new UnknownPaymentGatewayException("Payment gateway [{$name}] does not match its provider name.");
        }

        return $gateway;
    }

    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->gateways);
    }
}
