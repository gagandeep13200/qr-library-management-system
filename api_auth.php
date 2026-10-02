<?php
include_once 'db_connect.php';
include_once 'jwt.php';
header('Content-Type: application/json');

function require_user(string $role = ''): array {
    $h = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $_SERVER['HTTP_AUTHORIZATION']
         ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
         ?? ($h['Authorization'] ?? $h['authorization'] ?? '');
    $user = jwt_verify(trim(preg_replace('/^Bearer\s+/i', '', $auth)));
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalid ya expired']);
        exit;
    }
    if ($role !== '' && ($user['role'] ?? '') !== $role) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    return $user;
}