<?php
/**
 * ISS G5E — Unlock Enigme Step API
 * POST /php/api/unlock_step.php
 * Body JSON: { "step_order": 1 }
 *
 * Met a jour la colonne `unlocked` dans g5e_enigme_steps.
 * Appelee par cargo.js quand le joueur resout une enigme.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';

if (!authCheck()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifie']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST uniquement']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$stepOrder = isset($input['step_order']) ? (int)$input['step_order'] : 0;

if ($stepOrder < 1 || $stepOrder > 10) {
    http_response_code(400);
    echo json_encode(['error' => 'step_order invalide']);
    exit;
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare(
        "UPDATE g5e_enigme_steps SET unlocked = 1 WHERE step_order = :step"
    );
    $stmt->execute([':step' => $stepOrder]);

    echo json_encode(['success' => true, 'step_order' => $stepOrder]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
