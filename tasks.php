<?php
declare(strict_types=1);
require_once __DIR__ . '/utils.php';
$pdo = DB::conn();
$uid = require_login($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 1000;
    if ($status !== '') {
        $stmt = $pdo->prepare('SELECT id,name,status,priority,due_date,created_at FROM tasks WHERE user_id=? AND status=? ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$uid, $status, $limit]);
    } else {
        $stmt = $pdo->prepare('SELECT id,name,status,priority,due_date,created_at FROM tasks WHERE user_id=? ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$uid, $limit]);
    }
    $tasks = $stmt->fetchAll();
    respond(['ok' => true, 'tasks' => $tasks]);
}
if ($method === 'POST') {
    $in = $_POST ?: json_input();
    $name = isset($in['name']) ? trim((string)$in['name']) : '';
    $status = isset($in['status']) ? trim((string)$in['status']) : 'Pending';
    $priority = isset($in['priority']) ? trim((string)$in['priority']) : 'Low';
    $due_date = isset($in['due_date']) ? (string)$in['due_date'] : null;
    if ($name === '') {
        respond(['ok' => false, 'error' => 'invalid_input'], 400);
    }
    $stmt = $pdo->prepare('INSERT INTO tasks(user_id,name,status,priority,due_date,created_at) VALUES(?,?,?,?,?,NOW())');
    $stmt->execute([$uid, $name, $status, $priority, $due_date]);
    $id = intval($pdo->lastInsertId());
    $stmt = $pdo->prepare('SELECT id,name,status,priority,due_date,created_at FROM tasks WHERE id=?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    respond(['ok' => true, 'task' => $task], 201);
}
if ($method === 'PUT' || $method === 'PATCH') {
    $in = json_input();
    $id = isset($in['id']) ? intval($in['id']) : 0;
    if ($id <= 0) {
        respond(['ok' => false, 'error' => 'invalid_id'], 400);
    }
    $fields = [];
    $values = [];
    if (isset($in['name'])) {
        $fields[] = 'name=?';
        $values[] = trim((string)$in['name']);
    }
    if (isset($in['status'])) {
        $fields[] = 'status=?';
        $values[] = trim((string)$in['status']);
    }
    if (isset($in['priority'])) {
        $fields[] = 'priority=?';
        $values[] = trim((string)$in['priority']);
    }
    if (array_key_exists('due_date', $in)) {
        $fields[] = 'due_date=?';
        $values[] = $in['due_date'] !== null ? (string)$in['due_date'] : null;
    }
    if (empty($fields)) {
        respond(['ok' => false, 'error' => 'no_changes'], 400);
    }
    $values[] = $uid;
    $values[] = $id;
    $sql = 'UPDATE tasks SET ' . implode(',', $fields) . ' WHERE user_id=? AND id=?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $stmt = $pdo->prepare('SELECT id,name,status,priority,due_date,created_at FROM tasks WHERE user_id=? AND id=?');
    $stmt->execute([$uid, $id]);
    $task = $stmt->fetch();
    respond(['ok' => true, 'task' => $task]);
}
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        $in = json_input();
        $id = isset($in['id']) ? intval($in['id']) : 0;
    }
    if ($id <= 0) {
        respond(['ok' => false, 'error' => 'invalid_id'], 400);
    }
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE user_id=? AND id=?');
    $stmt->execute([$uid, $id]);
    respond(['ok' => true]);
}
respond(['ok' => false, 'error' => 'method_not_allowed'], 405);

