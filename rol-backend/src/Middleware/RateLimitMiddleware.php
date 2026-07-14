<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RateLimitMiddleware implements MiddlewareInterface
{
    private static array $requests = [];
    private int $limit = 60;
    private int $window = 60;

    public function process(Request $request, RequestHandler $handler): Response
    {
        $ip = $this->getClientIp($request);
        $route = $request->getUri()->getPath();
        $key = $ip . '|' . $route;
        $now = time();

        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = ['count' => 0, 'reset' => $now + $this->window];
        }

        $entry = &self::$requests[$key];

        if ($now >= $entry['reset']) {
            $entry['count'] = 0;
            $entry['reset'] = $now + $this->window;
        }

        $entry['count']++;
        $remaining = max(0, $this->limit - $entry['count']);
        $resetTime = $entry['reset'];

        if ($entry['count'] > $this->limit) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Too many requests']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-RateLimit-Remaining', (string) $remaining)
                ->withHeader('X-RateLimit-Reset', (string) $resetTime)
                ->withStatus(429);
        }

        $response = $handler->handle($request);
        return $response
            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
            ->withHeader('X-RateLimit-Reset', (string) $resetTime);
    }

    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
