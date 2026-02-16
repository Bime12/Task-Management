<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function start_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function respond(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function require_login(PDO $pdo): int {
    start_session();
    if (!isset($_SESSION['user_id'])) {
        respond(['ok' => false, 'error' => 'unauthorized'], 401);
    }
    return intval($_SESSION['user_id']);
}

function user_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT id,name,email,created_at FROM users WHERE id=?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    return $u ?: null;
}

