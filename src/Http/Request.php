<?php

namespace App\Http;

final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $server;

    public function __construct(array $server, array $query, array $post)
    {
        $this->server = $server;
        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $uri = $server['REQUEST_URI'] ?? '/';
        $this->path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $this->query = $query;
        $this->post = $post;
    }

    public static function fromGlobals(): self
    {
        return new self($_SERVER, $_GET, $_POST);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    public function header(string $key): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$normalized] ?? null;
    }

    public function clientIp(bool $trustProxy = false): string
    {
        if ($trustProxy) {
            $forwarded = $this->header('X-Forwarded-For');
            if ($forwarded) {
                $parts = explode(',', $forwarded);
                $ip = trim($parts[0]);
                if ($ip !== '') {
                    return $ip;
                }
            }
        }

        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
