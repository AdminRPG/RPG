<?php

namespace App\Controllers;

use App\Auth\JWTService;
use App\Models\Cuenta;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = json_decode((string) $request->getBody(), true);
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->json($response, [
                'success' => false, 'error' => 'Username and password are required',
            ], 400);
        }

        $user = DB::connection('mybb')->table('mybb_users')
            ->where('username', $username)
            ->first();

        if (!$user) {
            return $this->json($response, [
                'success' => false, 'error' => 'Invalid credentials',
            ], 401);
        }

        $salt = $user->salt;
        $expectedHash = md5(md5($salt) . md5($password));

        if ($expectedHash !== $user->password) {
            return $this->json($response, [
                'success' => false, 'error' => 'Invalid credentials',
            ], 401);
        }

        $cuenta = Cuenta::firstOrCreate(
            ['mybb_user_id' => $user->uid],
            ['max_slots' => 3, 'es_narrador' => false]
        );

        $payload = [
            'sub' => $user->uid,
            'cuenta_id' => $cuenta->id,
            'username' => $user->username,
        ];

        $token = JWTService::encode($payload);

        return $this->json($response, [
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_in' => (int) ($_ENV['ROL_JWT_EXPIRY'] ?? 3600),
            ],
        ]);
    }

    public function refresh(Request $request, Response $response): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json($response, [
                'success' => false, 'error' => 'Token required',
            ], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $payload = JWTService::decode($token);
        } catch (\Firebase\JWT\ExpiredException $e) {
            $payload = (array) $e->getPayload();
            if (!isset($payload['sub'])) {
                return $this->json($response, [
                    'success' => false, 'error' => 'Invalid token',
                ], 401);
            }
        } catch (\Exception $e) {
            return $this->json($response, [
                'success' => false, 'error' => 'Invalid token',
            ], 401);
        }

        $newPayload = [
            'sub' => $payload['sub'],
            'cuenta_id' => $payload['cuenta_id'] ?? null,
            'username' => $payload['username'] ?? null,
        ];

        $newToken = JWTService::encode($newPayload);

        return $this->json($response, [
            'success' => true,
            'data' => [
                'token' => $newToken,
                'expires_in' => (int) ($_ENV['ROL_JWT_EXPIRY'] ?? 3600),
            ],
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
