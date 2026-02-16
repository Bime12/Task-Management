<?php
declare(strict_types=1);
require_once __DIR__ . '/utils.php';
$pdo = DB::conn();
$uid = require_login($pdo);
$user = user_by_id($pdo, $uid);
respond(['ok' => true, 'user' => $user]);

