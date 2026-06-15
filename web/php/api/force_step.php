<?php
/**
 * ISS G5E — Force Step API (Game Master)
 * POST /php/api/force_step.php
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

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['step_order']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametres manquants']);
    exit;
}

$stepOrder = (int)$data['step_order'];
$action = $data['action'];

$pdo = getDB();

try {
    if ($action === 'unlock') {
        $stmt = $pdo->prepare('UPDATE g5e_enigme_steps SET unlocked = 1 WHERE step_order = :s');
        $stmt->execute([':s' => $stepOrder]);
        echo json_encode(['success' => true, 'message' => "Etape $stepOrder deverrouillee"]);
    } 
    elseif ($action === 'validate') {
        $session = $pdo->query("SELECT id FROM g5e_game_sessions ORDER BY id DESC LIMIT 1")->fetch();
        if (!$session) {
            echo json_encode(['error' => 'Aucune session de jeu active']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO g5e_alerts (session_id, type, message, severity) VALUES (:sid, 'etape_validee', :msg, 'info')");
        $stmt->execute([
            ':sid' => $session['id'],
            ':msg' => "Etape $stepOrder validee manuellement (GM bypass)"
        ]);
        
        // Mettre a jour la progression G5E
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM g5e_alerts WHERE session_id = :sid AND type = 'etape_validee'");
        $stmt->execute([':sid' => $session['id']]);
        $validatedCount = (int)$stmt->fetchColumn();
        
        $totalSteps = 3;
        $progressPct = min(100, round(($validatedCount / $totalSteps) * 100));
        
        $pdo->prepare("UPDATE progression SET progress = :pct WHERE salle = 'G5E'")->execute([':pct' => $progressPct]);
        
        if ($progressPct >= 100) {
            $pdo->prepare("INSERT INTO g5e_alerts (session_id, type, message, severity) VALUES (:sid, 'session_terminee', 'Session reussie (forçage manuel)', 'info')")->execute([':sid' => $session['id']]);
            $pdo->prepare("UPDATE g5e_game_sessions SET status = 'reussie', ended_at = NOW() WHERE id = :sid")->execute([':sid' => $session['id']]);
        }
        
        echo json_encode(['success' => true, 'message' => "Etape $stepOrder validee manuellement"]);
    } 
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
