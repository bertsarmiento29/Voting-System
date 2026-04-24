<?php
namespace App\Core;

class Router {
    private array $routes = [];
    private array $middleware = [];

    public function get(string $path, callable|array $handler): self {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, callable|array $handler): self {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    public function put(string $path, callable|array $handler): self {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    public function delete(string $path, callable|array $handler): self {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function addMiddleware(callable $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                foreach ($this->middleware as $middleware) {
                    $result = $middleware();
                    if ($result === false) {
                        return;
                    }
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $_GET = array_merge($_GET, $params);

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$controller, $method] = $handler;
                    $controllerFile = CONTROLLERS_PATH . '/' . $controller . '.php';
                    if (file_exists($controllerFile)) {
                        require_once $controllerFile;
                        $controllerInstance = new $controller();
                        call_user_func_array([$controllerInstance, $method], $params);
                    } else {
                        $this->notFound();
                    }
                } else {
                    call_user_func($handler, $params);
                }
                return;
            }
        }
        $this->notFound();
    }

    private function notFound(): void {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        exit;
    }
}
