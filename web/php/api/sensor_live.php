<?php
/**
 * ISS G5E — Sensor Live API (fast, file-based)
 * Reads latest.json written by serial/read_sensor.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../includes/auth.php';

if (!authCheck()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifie']);
    exit;
}

$liveFile = realpath(__DIR__ . '/../../../serial/latest.json');

if ($liveFile && file_exists($liveFile)) {
    $data = json_decode(file_get_contents($liveFile), true);
    if ($data) {
        $data['online'] = (time() - strtotime($data['timestamp'])) < 5;
        echo json_encode($data);
        exit;
    }
}

echo json_encode(['online' => false, 'adc_value' => 0, 'distance_cm' => 0]);
