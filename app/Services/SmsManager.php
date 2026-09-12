<?php

namespace App\Services;

use App\Contracts\Sms\SmsProvider;
use App\Exceptions\UnknownSmsProviderException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves a canonical provider name to an SMS provider implementation.
 *
 * Resolution goes exclusively through the server-side allowlist; the requested
 * name is never treated as a class name. Arbitrary user input therefore cannot
 * trigger instantiation of arbitrary classes.
 */
final class SmsManager
{
    /**
     * @param  array<string, class-string<SmsProvider>>  $providers  server-side allowlist
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $providers,
    ) {}

    public function resolve(string $name): SmsProvider
    {
        $class = $this->providers[$name] ?? null;

        if ($class === null) {
            throw new UnknownSmsProviderException("SMS provider [{$name}] is not configured.");
        }

        $provider = $this->container->make($class);

        if (! $provider instanceof SmsProvider) {
            throw new UnknownSmsProviderException("SMS provider [{$name}] does not implement the provider contract.");
        }

        if ($provider->name() !== $name) {
            throw new UnknownSmsProviderException("SMS provider [{$name}] does not match its provider name.");
        }

        return $provider;
    }

    /**
     * Resolve the active provider (default unless a name is given).
     *
     * @throws \App\Exceptions\UnknownSmsProviderException
     */
    public function provider(?string $name = null): SmsProvider
    {
        return $this->resolve($name ?? (string) config('sms.default'));
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->providers);
    }
}