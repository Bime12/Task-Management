<?php
declare(strict_types=1);
require_once __DIR__ . '/utils.php';

$pdo = DB::conn();
start_session();
$in = $_POST ?: json_input();
$email = isset($in['email']) ? strtolower(trim((string)$in['email'])) : '';
$password = isset($in['password']) ? (string)$in['password'] : '';
if ($email === '' || $password === '') {
    respond(['ok' => false, 'error' => 'invalid_input'], 400);
}
$stmt = $pdo->prepare('SELECT id,name,email,password_hash,created_at FROM users WHERE email=? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    respond(['ok' => false, 'error' => 'invalid_credentials'], 401);
}
$_SESSION['user_id'] = intval($user['id']);
unset($user['password_hash']);
respond(['ok' => true, 'user' => $user]);

