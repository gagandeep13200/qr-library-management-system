<?php
const JWT_SECRET = 'Hkc1nZ3Ww7JvAQbXpNqPUTrB9DYSx2ejILMfi0lyKFRC6mVG';

function b64url($d){ return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function b64url_dec($d){ return base64_decode(strtr($d, '-_', '+/')); }

function jwt_create(array $payload, int $ttl = 3600): string {
    $h = b64url(json_encode(['alg'=>'HS256','typ'=>'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl;
    $p = b64url(json_encode($payload));
    $s = b64url(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    return "$h.$p.$s";
}

function jwt_verify(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;
    $header = json_decode(b64url_dec($h), true);
    if (($header['alg'] ?? '') !== 'HS256') return null;
    $expected = b64url(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) return null;
    $payload = json_decode(b64url_dec($p), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) return null;
    return $payload;
}