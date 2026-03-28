<?php

declare(strict_types=1);

namespace Reqxide;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class RequestBuilder
{
    /** @var array<string, string> */
    private array $headers = [];

    private string|StreamInterface|null $body = null;

    private ?string $contentType = null;

    /** @var array<string, string> */
    private array $queryParams = [];

    public function __construct(
        private readonly Client $client,
        private readonly Psr17Factory $factory,
        private readonly string $method,
        private readonly string $uri,
    ) {}

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /** @param array<string, string> $headers */
    public function headers(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    public function bearerToken(string $token): self
    {
        $this->headers['Authorization'] = 'Bearer '.$token;

        return $this;
    }

    public function basicAuth(string $user, string $password): self
    {
        $this->headers['Authorization'] = 'Basic '.base64_encode($user.':'.$password);

        return $this;
    }

    public function body(string|StreamInterface $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function json(mixed $data): self
    {
        $this->body = json_encode($data, JSON_THROW_ON_ERROR);
        $this->contentType = 'application/json';

        return $this;
    }

    /** @param array<string, string> $data */
    public function form(array $data): self
    {
        $this->body = http_build_query($data);
        $this->contentType = 'application/x-www-form-urlencoded';

        return $this;
    }

    /** @param array<string, string> $params */
    public function query(array $params): self
    {
        $this->queryParams = $params;

        return $this;
    }

    public function send(): ResponseInterface
    {
        // Build the URI with query params
        $uri = $this->uri;
        if ($this->queryParams !== []) {
            $separator = str_contains($uri, '?') ? '&' : '?';
            $uri .= $separator.http_build_query($this->queryParams);
        }

        // Create PSR-7 Request via factory
        $request = $this->factory->createRequest($this->method, $uri);

        // Apply content type if set (before custom headers so user can override)
        if ($this->contentType !== null) {
            $request = $request->withHeader('Content-Type', $this->contentType);
        }

        // Apply headers (PSR-7 immutable — MUST reassign)
        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Apply body
        if ($this->body !== null) {
            $body = $this->body instanceof StreamInterface
                ? $this->body
                : $this->factory->createStream($this->body);
            $request = $request->withBody($body);
        }

        // Send via Client::sendRequest (PSR-18)
        return $this->client->sendRequest($request);
    }
}
