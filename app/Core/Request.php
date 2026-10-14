<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $bodyParams;
    private array $routeParams = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUrl = parse_url($requestUri);
        $this->uri = rawurldecode($parsedUrl['path'] ?? '/');

        // Normalização de barras
        if ($this->uri !== '/' && str_ends_with($this->uri, '/')) {
            $this->uri = rtrim($this->uri, '/');
        }

        $this->queryParams = $_GET;
        $this->bodyParams = $_POST;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->queryParams;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $default;
    }

    public function allPost(): array
    {
        return $this->bodyParams;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $this->bodyParams[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function csrfToken(): ?string
    {
        return $this->bodyParams['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
}
