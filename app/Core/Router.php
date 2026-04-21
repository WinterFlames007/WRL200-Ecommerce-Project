<?php

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => []
    ];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH);



        // Adjust if project is inside /workrelated/public or /workrelated
        $path = str_replace('/workrelated/public', '', $path);
        $path = str_replace('/workrelated', '', $path);

        if ($path === '') {
            $path = '/';
        }

        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo "404 - Page not found";
            return;
        }

        [$controllerName, $methodName] = explode('@', $handler);

        $controllerClass = "App\\Controllers\\$controllerName";

        if (!class_exists($controllerClass)) {
            die("Controller $controllerClass not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            die("Method $methodName not found in controller $controllerClass");
        }

        $controller->$methodName();
    }
}