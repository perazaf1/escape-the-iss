<?php
/**
 * ISS G5E — Reset API (dev / Game Master)
 * POST /php/api/reset.php
 * Resets: alerts, capteur_logs, game_sessions, progression G5E
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';

if (!authCheck()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifie']);
    exit;
}

$user = authUser();
if ($user['role'] !== 'game_master') {
    http_response_code(403);
    echo json_encode(['error' => 'Acces refuse : Game Master requis']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST uniquement']);
    exit;
}

$pdo = getDB();

try {
    $pdo->exec("DELETE FROM g5e_alerts");
    $pdo->exec("DELETE FROM g5e_capteur_logs");
    $pdo->exec("DELETE FROM g5e_game_sessions");
    $pdo->exec("UPDATE g5e_enigme_steps SET unlocked = 0");
    $pdo->exec("UPDATE progression SET progress = 0 WHERE salle = 'G5E'");

    echo json_encode(['success' => true, 'message' => 'Donnees G5E remises a zero']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
