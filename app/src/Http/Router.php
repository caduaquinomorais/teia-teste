<?php

declare(strict_types=1);

namespace Teia\Http;

defined('TEIA_ROOT') || exit;

/**
 * Roteador próprio com parâmetros dinâmicos por regex.
 *   $router->get('/secao/{slug:[a-z0-9-]+}', $handler);
 * Parâmetro sem regex usa [^/]+.
 */
final class Router
{
    /** @var array<int,array{method:string,regex:string,params:array,handler:callable,options:array}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, array $options = []): void
    {
        $this->add('GET', $pattern, $handler, $options);
    }

    public function post(string $pattern, callable $handler, array $options = []): void
    {
        $this->add('POST', $pattern, $handler, $options);
    }

    public function add(string $method, string $pattern, callable $handler, array $options = []): void
    {
        $params = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::((?:[^{}]+|\{[^{}]*\})+))?\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                $inner = $m[2] ?? '[^/]+';
                return '(' . $inner . ')';
            },
            $pattern
        );
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $regex . '$#u',
            'params' => $params,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    /**
     * @return array{handler:callable,params:array<string,string>,options:array}
     * @throws HttpException 404 ou 405
     */
    public function match(string $method, string $path): array
    {
        $allowed = [];
        $method = strtoupper($method);
        $lookup = $method === 'HEAD' ? 'GET' : $method;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $lookup) {
                $allowed[] = $route['method'];
                continue;
            }
            $values = array_slice($m, 1, count($route['params']));
            return [
                'handler' => $route['handler'],
                'params' => array_combine($route['params'], $values) ?: [],
                'options' => $route['options'],
            ];
        }
        if ($allowed !== []) {
            throw HttpException::methodNotAllowed(array_values(array_unique($allowed)));
        }
        throw HttpException::notFound();
    }
}
