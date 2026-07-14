<?php

namespace App\Middleware;

use App\Auth\JWTService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = substr($authHeader, 7);

        try {
            $payload = JWTService::decode($token);
        } catch (\Exception $e) {
            return $this->unauthorized();
        }

        if (empty($payload)) {
            return $this->unauthorized();
        }

        $request = $request
            ->withAttribute('user_id', $payload['sub'] ?? null)
            ->withAttribute('cuenta_id', $payload['cuenta_id'] ?? null);

        return $handler->handle($request);
    }

    private function unauthorized(): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
