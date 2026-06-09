<?php
/**
 * API Journal — Liste des alertes/événements G5E
 * GET ?severity=all&limit=100
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
authStart();

header('Content-Type: application/json');

if (!authCheck()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorise']);
    exit;
}

$pdo = getDB();

$severity = $_GET['severity'] ?? 'all';
$limit = min(max((int)($_GET['limit'] ?? 100), 1), 500);

$allowedSeverities = ['info', 'warning', 'critical'];

$sql = 'SELECT id, session_id, type, message, severity, created_at FROM g5e_alerts';
$params = [];

if ($severity !== 'all' && in_array($severity, $allowedSeverities, true)) {
    $sql .= ' WHERE severity = :severity';
    $params[':severity'] = $severity;
}

$sql .= ' ORDER BY created_at DESC LIMIT :limit';

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$alerts = $stmt->fetchAll();

// Count by severity
$countStmt = $pdo->query('SELECT severity, COUNT(*) as cnt FROM g5e_alerts GROUP BY severity');
$counts = ['info' => 0, 'warning' => 0, 'critical' => 0, 'total' => 0];
foreach ($countStmt->fetchAll() as $row) {
    $counts[$row['severity']] = (int)$row['cnt'];
    $counts['total'] += (int)$row['cnt'];
}

echo json_encode([
    'alerts' => $alerts,
    'count' => count($alerts),
    'counts' => $counts
]);
