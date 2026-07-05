<?php

namespace App\Auth;

class JWTService
{
    public static function encode(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($signature);
        return implode('.', $segments);
    }

    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return [];
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $signingInput = $headerB64 . '.' . $payloadB64;
        $signature = self::base64UrlDecode($signatureB64);
        $expectedSignature = hash_hmac('sha256', $signingInput, $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return [];
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        return is_array($payload) ? $payload : [];
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
