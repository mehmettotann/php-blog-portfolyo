<?php
declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<int, array{method:string,pattern:string,regex:string,params:string[],handler:callable}> */
    private array $routes = [];

    /** @var callable|null */
    private $notFoundHandler = null;

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function setNotFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $params = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use (&$params): string {
            $params[] = $m[1];
            return '([^/]+)';
        }, $pattern);

        $normalizedRegex = '#^' . rtrim((string) $regex, '/') . '/?$#u';
        if ($pattern === '/') {
            $normalizedRegex = '#^/$#';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $normalizedRegex,
            'params' => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uriPath): void
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = '/' . trim((string) parse_url($uriPath, PHP_URL_PATH), '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $args = [];
                foreach ($route['params'] as $i => $name) {
                    $args[$name] = isset($matches[$i]) ? rawurldecode($matches[$i]) : '';
                }
                ($route['handler'])($args);
                return;
            }
        }

        if ($this->notFoundHandler !== null) {
            ($this->notFoundHandler)();
            return;
        }

        http_response_code(404);
        echo '404 Sayfa Bulunamadı';
    }
}
