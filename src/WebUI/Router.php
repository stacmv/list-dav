<?php

declare(strict_types=1);

namespace ListDAV\WebUI;

class Router
{
    /** @var array<string, array{method: string, handler: callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[$path] = ['method' => 'GET', 'handler' => $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[$path] = ['method' => 'POST', 'handler' => $handler];
    }

    public function dispatch(string $method, string $path): mixed
    {
        // Try exact match first
        if (isset($this->routes[$path]) && $this->routes[$path]['method'] === $method) {
            return ($this->routes[$path]['handler'])();
        }

        // Try pattern matching
        foreach ($this->routes as $routePath => $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return ($route['handler'])(...array_values($params));
            }
        }

        return null;
    }
}
