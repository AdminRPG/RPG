<?php

namespace App\Auth;

class JWTService
{
    public static function encode(array $payload, string $secret): string {}
    public static function decode(string $token, string $secret): array {}
}
