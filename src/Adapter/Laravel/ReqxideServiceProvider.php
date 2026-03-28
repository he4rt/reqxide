<?php

declare(strict_types=1);

namespace Reqxide\Adapter\Laravel;

use Reqxide\Client;
use Reqxide\Contract\TransportInterface;
use Reqxide\Emulation\Browser;
use Reqxide\Transport\TransportFactory;

/**
 * Laravel integration helper.
 *
 * Provides factory methods for creating reqxide services
 * that can be registered in a Laravel service container.
 *
 * In a Laravel ServiceProvider:
 *   public function register(): void {
 *       $helper = new \Reqxide\Adapter\Laravel\ReqxideServiceProvider();
 *       $this->app->singleton(TransportInterface::class, fn() => $helper->createTransport());
 *       $this->app->singleton(Client::class, fn() => $helper->createClient());
 *   }
 */
final class ReqxideServiceProvider
{
    public function __construct(
        private readonly ?string $defaultBrowser = null,
        private readonly ?string $libraryPath = null,
    ) {}

    public function createTransport(): TransportInterface
    {
        return TransportFactory::create();
    }

    public function createClient(?Browser $browser = null): Client
    {
        $resolvedBrowser = $browser ?? $this->resolveDefaultBrowser();

        $builder = Client::builder();

        if ($resolvedBrowser !== null) {
            $builder = $builder->emulation($resolvedBrowser);
        }

        return $builder->build();
    }

    public function resolveDefaultBrowser(): ?Browser
    {
        if ($this->defaultBrowser === null) {
            return null;
        }

        return Browser::tryFrom($this->defaultBrowser);
    }

    public function libraryPath(): ?string
    {
        return $this->libraryPath;
    }
}
