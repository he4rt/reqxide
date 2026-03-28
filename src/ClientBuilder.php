<?php

declare(strict_types=1);

namespace Reqxide;

use Reqxide\Contract\CookieStoreInterface;
use Reqxide\Contract\RedirectPolicyInterface;
use Reqxide\Contract\RetryPolicyInterface;
use Reqxide\Contract\TransportInterface;
use Reqxide\Cookie\CookieJar;
use Reqxide\Emulation\Browser;
use Reqxide\Emulation\Profile;
use Reqxide\Middleware\CompressionMiddleware;
use Reqxide\Middleware\CookieMiddleware;
use Reqxide\Middleware\MiddlewareInterface;
use Reqxide\Middleware\MiddlewarePipeline;
use Reqxide\Middleware\RedirectMiddleware;
use Reqxide\Middleware\RetryMiddleware;
use Reqxide\Proxy\Proxy;
use Reqxide\Transport\TransportFactory;
use Reqxide\Transport\TransportOptions;

final class ClientBuilder
{
    private ?Browser $browser = null;

    private ?Profile $profile = null;

    private ?TransportInterface $transport = null;

    private ?Proxy $proxy = null;

    private int $timeoutMs = 30_000;

    private int $connectTimeoutMs = 10_000;

    private bool $cookieStoreEnabled = false;

    private ?CookieStoreInterface $cookieJar = null;

    private ?RedirectPolicyInterface $redirectPolicy = null;

    private ?RetryPolicyInterface $retryPolicy = null;

    /** @var array<string, string> */
    private array $defaultHeaders = [];

    private bool $verifySsl = true;

    private ?string $caBundle = null;

    public function emulation(Browser $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function profile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function transport(TransportInterface $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function proxy(Proxy $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeoutMs = $seconds * 1000;

        return $this;
    }

    public function connectTimeout(int $seconds): self
    {
        $this->connectTimeoutMs = $seconds * 1000;

        return $this;
    }

    public function cookieStore(bool|CookieStoreInterface $store = true): self
    {
        if ($store instanceof CookieStoreInterface) {
            $this->cookieJar = $store;
            $this->cookieStoreEnabled = true;
        } else {
            $this->cookieStoreEnabled = $store;
        }

        return $this;
    }

    public function redirect(RedirectPolicyInterface $policy): self
    {
        $this->redirectPolicy = $policy;

        return $this;
    }

    public function retry(RetryPolicyInterface $policy): self
    {
        $this->retryPolicy = $policy;

        return $this;
    }

    /** @param array<string, string> $headers */
    public function defaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;

        return $this;
    }

    public function verify(bool $verify): self
    {
        $this->verifySsl = $verify;

        return $this;
    }

    public function caBundle(string $path): self
    {
        $this->caBundle = $path;

        return $this;
    }

    public function build(): Client
    {
        // Resolve profile
        $profile = $this->profile ?? $this->browser?->profile() ?? new Profile;

        // Resolve transport
        $transport = $this->transport ?? TransportFactory::create();

        // Build transport options
        $transportOptions = new TransportOptions(
            timeoutMs: $this->timeoutMs,
            connectTimeoutMs: $this->connectTimeoutMs,
            verifySsl: $this->verifySsl,
            caBundle: $this->caBundle,
            proxy: $this->proxy,
        );

        // Build middleware pipeline
        // Phase 5 will add Cookie, Redirect, Retry, Compression middlewares using:
        //   $this->cookieStoreEnabled, $this->cookieJar, $this->redirectPolicy, $this->retryPolicy
        $middlewares = $this->buildMiddlewares();
        $pipeline = new MiddlewarePipeline($middlewares);

        return new Client(
            profile: $profile,
            transport: $transport,
            transportOptions: $transportOptions,
            pipeline: $pipeline,
            defaultHeaders: $this->defaultHeaders,
        );
    }

    /** @return list<MiddlewareInterface> */
    private function buildMiddlewares(): array
    {
        $middlewares = [];

        if ($this->cookieStoreEnabled || $this->cookieJar instanceof CookieStoreInterface) {
            $middlewares[] = new CookieMiddleware(
                $this->cookieJar ?? new CookieJar,
            );
        }

        if ($this->redirectPolicy instanceof RedirectPolicyInterface) {
            $middlewares[] = new RedirectMiddleware($this->redirectPolicy);
        }

        if ($this->retryPolicy instanceof RetryPolicyInterface) {
            $middlewares[] = new RetryMiddleware($this->retryPolicy);
        }

        $middlewares[] = new CompressionMiddleware;

        return $middlewares;
    }
}
