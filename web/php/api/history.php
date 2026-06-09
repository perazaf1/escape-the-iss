<?php
/**
 * ISS G5E — Sensor History API
 * Returns distance_cm + created_at arrays for Chart.js
 * Filterable by period: 5min, 15min, 1h, 6h, 24h
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../includes/auth.php';

if (!authCheck()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifie']);
    exit;
}

$period = $_GET['period'] ?? '15min';

$intervals = [
    '5min'  => 'INTERVAL 5 MINUTE',
    '15min' => 'INTERVAL 15 MINUTE',
    '1h'    => 'INTERVAL 1 HOUR',
    '6h'    => 'INTERVAL 6 HOUR',
    '24h'   => 'INTERVAL 24 HOUR'
];

if (!isset($intervals[$period])) {
    $period = '15min';
}

$pdo = getDB();

$sql = "SELECT distance_cm, adc_value, created_at
        FROM g5e_capteur_logs
        WHERE created_at >= NOW() - {$intervals[$period]}
        ORDER BY created_at ASC";

// Limit points for large periods to avoid overloading Chart.js
$maxPoints = [
    '5min'  => 500,
    '15min' => 500,
    '1h'    => 1000,
    '6h'    => 2000,
    '24h'   => 3000
];

$sql .= ' LIMIT ' . $maxPoints[$period];

$rows = $pdo->query($sql)->fetchAll();

$labels = [];
$distances = [];
$adc = [];

foreach ($rows as $row) {
    $labels[]    = $row['created_at'];
    $distances[] = (int)$row['distance_cm'];
    $adc[]       = (int)$row['adc_value'];
}

echo json_encode([
    'labels'    => $labels,
    'distances' => $distances,
    'adc'       => $adc,
    'period'    => $period,
    'count'     => count($rows)
]);
