<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        // Normaliza a rota
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        // Converte parâmetros dinâmicos {param} em regex named capture
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        $allowedMethods = [];
        $matchedRoute = null;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                if ($route['method'] === $method) {
                    $matchedRoute = $route;
                    // Extrai apenas os parâmetros nomeados
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $request->setRouteParams($params);
                    break;
                } else {
                    $allowedMethods[] = $route['method'];
                }
            }
        }

        if ($matchedRoute === null) {
            if (!empty($allowedMethods)) {
                http_response_code(405);
                echo View::render('errors/405', ['allowed' => implode(', ', array_unique($allowedMethods))], 'layouts/main');
                return;
            }

            http_response_code(404);
            echo View::render('errors/404', ['uri' => $uri], 'layouts/main');
            return;
        }

        // Executa Middleware
        foreach ($matchedRoute['middlewares'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $result = $middleware->handle($request);
            if ($result === false) {
                return; // O middleware interceptou e redirecionou ou encerrou a requisição
            }
        }

        $handler = $matchedRoute['handler'];

        try {
            if (is_callable($handler)) {
                $response = call_user_func($handler, $request);
            } elseif (is_array($handler) && count($handler) === 2) {
                [$controllerClass, $action] = $handler;
                $controller = Container::resolve($controllerClass);
                $response = $controller->$action($request);
            } else {
                throw new \RuntimeException("Handler de rota inválido.");
            }

            if ($response instanceof Response) {
                $response->send();
            } elseif (is_string($response)) {
                echo $response;
            }
        } catch (\Throwable $e) {
            // Em produção/demonstração segura, renderiza página 500 amigável sem expor senhas
            http_response_code(500);
            echo View::render('errors/500', ['error' => $e->getMessage()], 'layouts/main');
        }
    }
}
