<?php
/**
 * ISS G5E — Dashboard API
 * Returns JSON: rooms progress, sensor data, enigme steps, session, alerts
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../includes/auth.php';

if (!authCheck()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifie']);
    exit;
}

$pdo = getDB();

// 1. All 5 rooms progress
$rooms = $pdo->query('SELECT salle, progress, nom_usage FROM progression ORDER BY salle ASC')->fetchAll();

// 2. Latest sensor reading
$sensor = $pdo->query(
    'SELECT adc_value, distance_cm, created_at FROM g5e_capteur_logs ORDER BY id DESC LIMIT 1'
)->fetch();

// 3. Sensor stats (last 60 seconds)
$sensorStats = $pdo->query(
    'SELECT AVG(distance_cm) as avg_dist, MIN(distance_cm) as min_dist, MAX(distance_cm) as max_dist, COUNT(*) as readings
     FROM g5e_capteur_logs WHERE created_at >= NOW() - INTERVAL 60 SECOND'
)->fetch();

// 4. Current game session (most recent active or last one)
$session = $pdo->query(
    "SELECT id, status, started_at, ended_at, duration_seconds
     FROM g5e_game_sessions ORDER BY id DESC LIMIT 1"
)->fetch();

// 5. Enigme steps + validation status
$steps = $pdo->query('SELECT * FROM g5e_enigme_steps ORDER BY step_order ASC')->fetchAll();

// Check which steps have been validated in current session
if ($session) {
    $stmtValidated = $pdo->prepare(
        "SELECT message FROM g5e_alerts WHERE session_id = :sid AND type = 'etape_validee'"
    );
    $stmtValidated->execute([':sid' => $session['id']]);
    $validatedMessages = $stmtValidated->fetchAll(PDO::FETCH_COLUMN);

    foreach ($steps as &$step) {
        $step['validated'] = false;
        foreach ($validatedMessages as $msg) {
            if (preg_match('/^Etape ' . $step['step_order'] . ' validee/', $msg)) {
                $step['validated'] = true;
                break;
            }
        }
    }
    unset($step);
} else {
    foreach ($steps as &$step) {
        $step['validated'] = false;
    }
    unset($step);
}

// 6. Recent alerts (last 10)
$alerts = $pdo->query(
    'SELECT type, message, severity, created_at FROM g5e_alerts ORDER BY id DESC LIMIT 10'
)->fetchAll();

// 7. Sensor history (last 30 readings for sparkline)
$history = $pdo->query(
    'SELECT distance_cm, created_at FROM g5e_capteur_logs ORDER BY id DESC LIMIT 30'
)->fetchAll();
$history = array_reverse($history);

echo json_encode([
    'rooms'       => $rooms,
    'sensor'      => $sensor ?: null,
    'sensorStats' => $sensorStats ?: null,
    'session'     => $session ?: null,
    'steps'       => $steps,
    'alerts'      => $alerts,
    'history'     => $history,
    'serverTime'  => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
