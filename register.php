<?php
declare(strict_types=1);
require_once __DIR__ . '/utils.php';

$pdo = DB::conn();
start_session();
$in = $_POST ?: json_input();
$name = isset($in['name']) ? trim((string)$in['name']) : '';
$email = isset($in['email']) ? strtolower(trim((string)$in['email'])) : '';
$password = isset($in['password']) ? (string)$in['password'] : '';
if ($name === '' || $email === '' || $password === '') {
    respond(['ok' => false, 'error' => 'invalid_input'], 400);
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    respond(['ok' => false, 'error' => 'email_exists'], 409);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash,created_at) VALUES(?,?,?,NOW())');
$stmt->execute([$name, $email, $hash]);
respond(['ok' => true]);

