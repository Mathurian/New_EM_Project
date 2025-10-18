<?php
declare(strict_types=1);
namespace App;

class Router {
	private array $routes = ['GET'=>[], 'POST'=>[]];

	public function get(string $pattern, string $action): void { $this->add('GET', $pattern, $action); }
	public function post(string $pattern, string $action): void { $this->add('POST', $pattern, $action); }

	private function add(string $method, string $pattern, string $action): void {
		$regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
		$this->routes[$method][] = [$regex, $action];
	}

	public function dispatch(): void {
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
		foreach ($this->routes[$method] as [$regex, $action]) {
			if (preg_match($regex, $uri, $matches)) {
				[$controller, $methodName] = explode('@', $action);
				$controllerClass = 'App\\Routes\\' . $controller;
				$params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
				(new $controllerClass())->$methodName($params);
				return;
			}
		}
		http_response_code(404);
		view('404');
	}
}


