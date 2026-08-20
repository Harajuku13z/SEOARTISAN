<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;
use Closure;

final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,params:array<int,string>,handler:mixed,middleware:array<int,string>,name:?string}> */
    private array $routes = [];

    /** @var array{prefix:string,middleware:array<int,string>}[] */
    private array $groupStack = [];

    private mixed $fallback = null;

    public function __construct(private Container $container)
    {
    }

    public function get(string $uri, mixed $handler): self
    {
        return $this->addRoute('GET', $uri, $handler);
    }

    public function post(string $uri, mixed $handler): self
    {
        return $this->addRoute('POST', $uri, $handler);
    }

    public function put(string $uri, mixed $handler): self
    {
        return $this->addRoute('PUT', $uri, $handler);
    }

    public function patch(string $uri, mixed $handler): self
    {
        return $this->addRoute('PATCH', $uri, $handler);
    }

    public function delete(string $uri, mixed $handler): self
    {
        return $this->addRoute('DELETE', $uri, $handler);
    }

    public function fallback(mixed $handler): void
    {
        $this->fallback = $handler;
    }

    /** @param array{prefix?:string,middleware?:array<int,string>} $attributes */
    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = [
            'prefix' => $attributes['prefix'] ?? '',
            'middleware' => $attributes['middleware'] ?? [],
        ];

        $callback($this);

        array_pop($this->groupStack);
    }

    public function name(string $name): self
    {
        $last = array_key_last($this->routes);
        if ($last !== null) {
            $this->routes[$last]['name'] = $name;
        }

        return $this;
    }

    private function addRoute(string $method, string $uri, mixed $handler): self
    {
        [$prefix, $middleware] = $this->currentGroupContext();
        $fullUri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        $fullUri = $fullUri === '' ? '/' : $fullUri;

        [$regex, $params] = $this->compile($fullUri);

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullUri,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
            'name' => null,
        ];

        return $this;
    }

    /** @return array{0:string,1:array<int,string>} */
    private function currentGroupContext(): array
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'], '/');
            $middleware = array_merge($middleware, $group['middleware']);
        }

        return [trim($prefix, '/'), $middleware];
    }

    /** @return array{0:string,1:array<int,string>} */
    private function compile(string $uri): array
    {
        $paramNames = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function (array $m) use (&$paramNames) {
            $paramNames[] = $m[1];

            return '([^/]+)';
        }, $uri);

        return ['#^' . $regex . '$#', $paramNames];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = array_combine($route['params'], $matches) ?: [];

            return $this->runPipeline($request, $route['middleware'], function (Request $request) use ($route, $params) {
                return $this->callHandler($route['handler'], $request, $params);
            });
        }

        if ($this->fallback !== null) {
            return $this->callHandler($this->fallback, $request, []);
        }

        return Response::html('<h1>404</h1><p>Page introuvable.</p>', 404);
    }

    /** @param array<int,string> $middlewareEntries */
    private function runPipeline(Request $request, array $middlewareEntries, Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middlewareEntries),
            function (Closure $next, string $entry) {
                return function (Request $request) use ($next, $entry) {
                    [$middlewareClass, $params] = $this->parseMiddlewareEntry($entry);
                    /** @var MiddlewareInterface $middleware */
                    $middleware = $this->container->make($middlewareClass);

                    return $middleware->handle($request, $next, ...$params);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    /** @return array{0:string,1:array<int,string>} */
    private function parseMiddlewareEntry(string $entry): array
    {
        if (!str_contains($entry, ':')) {
            return [$entry, []];
        }

        [$class, $paramString] = explode(':', $entry, 2);

        return [$class, explode(',', $paramString)];
    }

    /** @param array<string,string> $params */
    private function callHandler(mixed $handler, Request $request, array $params): Response
    {
        if ($handler instanceof Closure) {
            return $handler($request, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = $this->container->make($class);

            return $controller->{$method}($request, $params);
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = $this->container->make($class);

            return $controller->{$method}($request, $params);
        }

        throw new \RuntimeException('Unresolvable route handler.');
    }

    public function urlFor(string $name, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                $uri = $route['pattern'];
                foreach ($params as $key => $value) {
                    $uri = str_replace('{' . $key . '}', (string) $value, $uri);
                }

                return $uri;
            }
        }

        return '/';
    }
}
