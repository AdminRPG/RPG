<?php

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JWTService
{
    public static function encode(array $payload): string
    {
        $secret = self::getSecret();

        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + (int) ($_ENV['ROL_JWT_EXPIRY'] ?? 3600);
        }

        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function decode(string $token): array
    {
        $secret = self::getSecret();
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        if ($decoded instanceof \stdClass) {
            return (array) $decoded;
        }

        return [];
    }

    public static function validate(array $payload): bool
    {
        if (!isset($payload['exp'], $payload['sub'])) {
            return false;
        }

        return $payload['exp'] > time();
    }

    public static function getSecret(): string
    {
        $secret = $_ENV['ROL_JWT_SECRET'] ?? '';

        if (empty($secret) || $secret === 'change-this-to-a-random-secret') {
            $secret = bin2hex(random_bytes(32));
        }

        return $secret;
    }
}
