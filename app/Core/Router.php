<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middlewares = [], ?string $permission = null): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares, $permission);
    }

    public function post(string $path, array|callable $handler, array $middlewares = [], ?string $permission = null): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares, $permission);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares, ?string $permission): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'permission' => $permission,
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

        // Executa cadeia de Middlewares passando permissão declarada na rota
        foreach ($matchedRoute['middlewares'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $result = $middleware->handle($request, $matchedRoute['permission']);
            if ($result === false) {
                return;
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
            error_log('[ERROR 500] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            http_response_code(500);

            $isProduction = (Env::get('APP_ENV', 'production') === 'production');
            $errorMessage = $isProduction
                ? 'Ocorreu um erro interno durante o processamento da sua solicitação. O incidente foi registrado para análise da equipe técnica.'
                : $e->getMessage() . ' (em ' . basename($e->getFile()) . ':' . $e->getLine() . ')';

            echo View::render('errors/500', ['error' => $errorMessage], 'layouts/main');
        }
    }
}
