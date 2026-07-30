<?php

namespace App\Core;

class Router {
    private static array $routes = [];

    public static function get(string $path, array $handler): void {
        self::$routes['GET'][$path] = $handler;
    }

    public static function post(string $path, array $handler): void {
        self::$routes['POST'][$path] = $handler;
    }

    public static function dispatch(string $method, string $path): void {
        if (isset(self::$routes[$method][$path])) {
            [$class, $action] = self::$routes[$method][$path];
            $controller = new $class();
            $controller->$action();
        } else {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Endpoint/Route tidak ditemukan']);
        }
    }
}
