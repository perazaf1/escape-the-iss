<?php
/**
 * Escape Game ISS — Salle de Stockage (G5E)
 * Lecture du port serie + insertion BDD + validation automatique des etapes
 *
 * Mecanique de jeu :
 *   - 3 objets a placer a des distances cibles (35, 20, 50 cm)
 *   - Le joueur place un objet devant le capteur
 *   - Si la distance est dans la zone de tolerance pendant 3 secondes → etape validee
 *   - Les etapes se valident dans l'ordre (1 puis 2 puis 3 puis 4)
 *   - Toutes les etapes validees = enigme reussie
 *
 * Format TIVA : ADC:1234;DIST:45\n (decimal)
 * Usage : C:\xampp\php\php.exe read_sensor.php
 */

// --- Config serie ---
$portName = 'COM4';
$baudRate = 9600;
$bits = 8;
$stopBit = 1;

// --- Config validation ---
$HOLD_SECONDS = 3;  // Temps de maintien dans la zone pour valider

// --- Fichier live (lecture instantanee par le dashboard) ---
$liveFile = __DIR__ . '/latest.json';

// --- Config BDD ---
$configFile = __DIR__ . '/../web/php/includes/db.config.php';
if (!file_exists($configFile)) {
    echo "[ERREUR] Fichier db.config.php manquant. Copier db.config.example.php vers db.config.php.\n";
    exit(1);
}
$cfg = require $configFile;
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "[OK] Connexion BDD etablie.\n";
} catch (PDOException $e) {
    die("[ERREUR] Connexion BDD : " . $e->getMessage() . "\n");
}

// --- Charger les etapes de l'enigme ---
$steps = $pdo->query('SELECT * FROM g5e_enigme_steps ORDER BY step_order ASC')->fetchAll();
echo "[OK] " . count($steps) . " etapes chargees.\n";

foreach ($steps as $s) {
    $lockIcon = !empty($s['unlocked']) ? 'DEVERROUILLE' : 'VERROUILLE';
    echo "     Etape {$s['step_order']}: {$s['label']} → {$s['target_distance_cm']} cm (± {$s['tolerance_cm']} cm) [{$lockIcon}]\n";
}

// --- Trouver ou creer une session active ---
$session = $pdo->query(
    "SELECT id FROM g5e_game_sessions WHERE status = 'en_cours' ORDER BY id DESC LIMIT 1"
)->fetch();

if (!$session) {
    // Creer une session auto (started_by = 1, premier user)
    $firstUser = $pdo->query('SELECT id FROM g5e_users ORDER BY id ASC LIMIT 1')->fetch();
    $startedBy = $firstUser ? $firstUser['id'] : 1;

    $pdo->prepare(
        "INSERT INTO g5e_game_sessions (started_by, status) VALUES (:uid, 'en_cours')"
    )->execute([':uid' => $startedBy]);

    $sessionId = (int)$pdo->lastInsertId();
    echo "[OK] Nouvelle session #$sessionId creee.\n";
} else {
    $sessionId = (int)$session['id'];
    echo "[OK] Session active #$sessionId trouvee.\n";
}

// --- Charger les etapes deja validees dans cette session ---
$validatedSteps = [];
$alertRows = $pdo->prepare(
    "SELECT message FROM g5e_alerts WHERE session_id = :sid AND type = 'etape_validee'"
);
$alertRows->execute([':sid' => $sessionId]);
foreach ($alertRows->fetchAll() as $row) {
    if (preg_match('/Etape (\d+)/', $row['message'], $m)) {
        $validatedSteps[] = (int)$m[1];
    }
}

echo "[OK] Etapes deja validees : " . (empty($validatedSteps) ? 'aucune' : implode(', ', $validatedSteps)) . "\n";

// --- Trouver l'etape courante (doit etre non-validee ET deverrouillee) ---
function getCurrentStep($steps, $validatedSteps) {
    foreach ($steps as $step) {
        if (!in_array((int)$step['step_order'], $validatedSteps, true)) {
            // L'etape n'est pas encore validee
            if (!empty($step['unlocked'])) {
                return $step; // Deverrouillee → on peut la valider
            } else {
                return null; // Prochaine etape pas encore deverrouillee → attendre
            }
        }
    }
    return null; // Toutes validees
}

$currentStep = getCurrentStep($steps, $validatedSteps);
if ($currentStep) {
    echo "[JEU] Etape courante : {$currentStep['step_order']} — {$currentStep['label']} ({$currentStep['target_distance_cm']} cm)\n";
} else {
    echo "[JEU] Toutes les etapes sont deja validees !\n";
}

// --- Ouverture port serie ---
$serialPort = dio_open("\\\\.\\{$portName}", O_RDWR);

if (!$serialPort) {
    die("[ERREUR] Impossible d'ouvrir le port {$portName}\n");
}

$output = [];
exec("mode {$portName} baud={$baudRate} data={$bits} stop={$stopBit} parity=n xon=on", $output);
echo "[OK] Port {$portName} configure a {$baudRate} bauds.\n";

echo "[OK] Lecture du capteur en cours... (Ctrl+C pour arreter)\n";
echo "---------------------------------------------------\n";

// --- Preparer les requetes ---
$stmtInsert = $pdo->prepare(
    "INSERT INTO g5e_capteur_logs (session_id, adc_value, distance_cm, created_at) VALUES (:sid, :adc, :dist, NOW())"
);
$stmtAlert = $pdo->prepare(
    "INSERT INTO g5e_alerts (session_id, type, message, severity, created_at) VALUES (:sid, :type, :msg, :sev, NOW())"
);
$stmtProgress = $pdo->prepare(
    "UPDATE progression SET progress = :progress WHERE salle = 'G5E'"
);
$stmtSession = $pdo->prepare(
    "UPDATE g5e_game_sessions SET status = :status, ended_at = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()) WHERE id = :sid"
);

// --- Boucle de lecture ---
$buffer = '';
$readCount = 0;
$lastDbInsert = 0;

// Validation : temps dans la zone
$inZoneSince = null;  // timestamp quand on entre dans la zone
$wasInZone = false;
$outOfZoneCount = 0;  // compteur de lectures hors zone consecutives
$MAX_OUT_OF_ZONE = 2; // tolerer jusqu'a 2 lectures hors zone (bruit capteur)

// Lissage : moyenne glissante sur N lectures
$SMOOTHING_WINDOW = 5;
$distanceBuffer = [];

// Resync : verifier si la session existe toujours (apres un reset web)
$lastResyncCheck = microtime(true);
$RESYNC_INTERVAL = 5.0; // verifier toutes les 5 secondes

function smoothDistance($distanceBuffer, $windowSize) {
    if (empty($distanceBuffer)) return 0;
    $slice = array_slice($distanceBuffer, -$windowSize);
    return (int)round(array_sum($slice) / count($slice));
}

while (true) {
    $data = dio_read($serialPort, 256);

    if ($data) {
        $buffer .= $data;

        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);

            if (empty($line)) continue;

            if ($line === 'ISS_CARGO_START') {
                echo "[TIVA] Carte connectee et prete.\n";
                continue;
            }

            if (preg_match('/^ADC:(\d+);DIST:(\d+)$/', $line, $matches)) {
                $adcVal = (int)$matches[1];
                $distRaw = (int)$matches[2];
                $now = microtime(true);

                // --- Lissage par moyenne glissante ---
                $distanceBuffer[] = $distRaw;
                if (count($distanceBuffer) > $SMOOTHING_WINDOW * 2) {
                    $distanceBuffer = array_slice($distanceBuffer, -$SMOOTHING_WINDOW);
                }
                $distVal = smoothDistance($distanceBuffer, $SMOOTHING_WINDOW);

                // --- Resync : recharger etapes (unlocked) + verifier session ---
                if ($now - $lastResyncCheck >= $RESYNC_INTERVAL) {
                    $lastResyncCheck = $now;
                    try {
                        // Toujours recharger les etapes (pour detecter les unlock depuis le web)
                        $oldCurrentStep = $currentStep ? (int)$currentStep['step_order'] : null;
                        $steps = $pdo->query('SELECT * FROM g5e_enigme_steps ORDER BY step_order ASC')->fetchAll();
                        $currentStep = getCurrentStep($steps, $validatedSteps);
                        $newCurrentStep = $currentStep ? (int)$currentStep['step_order'] : null;

                        if ($oldCurrentStep !== $newCurrentStep && $newCurrentStep !== null) {
                            echo "\n[UNLOCK] Etape {$newCurrentStep} deverrouillee ! Validation activee.\n";
                        }

                        // Verifier que la session existe toujours
                        $checkSession = $pdo->prepare(
                            "SELECT id, status FROM g5e_game_sessions WHERE id = :sid"
                        );
                        $checkSession->execute([':sid' => $sessionId]);
                        $sessionRow = $checkSession->fetch();
                        if (!$sessionRow) {
                            // Session supprimee (reset web) — resynchroniser
                            echo "\n[RESYNC] Session #$sessionId supprimee. Resynchronisation...\n";

                            // Trouver ou creer une nouvelle session
                            $newSession = $pdo->query(
                                "SELECT id FROM g5e_game_sessions WHERE status = 'en_cours' ORDER BY id DESC LIMIT 1"
                            )->fetch();

                            if (!$newSession) {
                                $firstUser = $pdo->query('SELECT id FROM g5e_users ORDER BY id ASC LIMIT 1')->fetch();
                                $startedBy = $firstUser ? $firstUser['id'] : 1;
                                $pdo->prepare(
                                    "INSERT INTO g5e_game_sessions (started_by, status) VALUES (:uid, 'en_cours')"
                                )->execute([':uid' => $startedBy]);
                                $sessionId = (int)$pdo->lastInsertId();
                            } else {
                                $sessionId = (int)$newSession['id'];
                            }

                            // Reset etat validation
                            $validatedSteps = [];
                            $currentStep = getCurrentStep($steps, $validatedSteps);
                            $wasInZone = false;
                            $inZoneSince = null;
                            $outOfZoneCount = 0;

                            echo "[RESYNC] Nouvelle session #$sessionId. Etape courante : " .
                                ($currentStep ? $currentStep['step_order'] : 'aucune (verrouille)') . "\n";
                        }
                    } catch (PDOException $e) {
                        // Ignorer les erreurs de resync
                    }
                }

                // --- Fichier live ---
                $liveData = [
                    'adc_value'    => $adcVal,
                    'distance_cm'  => $distVal,
                    'distance_raw' => $distRaw,
                    'timestamp'    => date('Y-m-d H:i:s'),
                    'session_id'   => $sessionId,
                    'current_step' => $currentStep ? (int)$currentStep['step_order'] : null,
                    'in_zone'      => false,
                    'hold_time'    => 0
                ];

                // --- Logique de validation ---
                if ($currentStep) {
                    $target = (int)$currentStep['target_distance_cm'];
                    $tolerance = (int)$currentStep['tolerance_cm'];
                    $diff = abs($distVal - $target);
                    $isInZone = $diff <= $tolerance;

                    if ($isInZone) {
                        $outOfZoneCount = 0; // reset le compteur de sorties

                        if (!$wasInZone) {
                            // Vient d'entrer dans la zone
                            $inZoneSince = $now;
                            $wasInZone = true;
                            echo "[ZONE] Objet detecte dans la zone de l'etape {$currentStep['step_order']} ({$distVal} cm lisse, cible {$target} cm)\n";
                        }

                        $holdTime = $now - $inZoneSince;
                        $liveData['in_zone'] = true;
                        $liveData['hold_time'] = round($holdTime, 1);

                        // Afficher le compteur
                        $remaining = $HOLD_SECONDS - $holdTime;
                        if ($remaining > 0) {
                            echo "\r[HOLD] Maintenir... " . number_format($remaining, 1) . "s restantes   ";
                        }

                        // Validation apres HOLD_SECONDS
                        if ($holdTime >= $HOLD_SECONDS) {
                            $stepOrder = (int)$currentStep['step_order'];
                            $validatedSteps[] = $stepOrder;

                            echo "\n[VALIDE] ★ Etape {$stepOrder} validee ! ({$currentStep['label']})\n";

                            // Inserer alerte de validation
                            $stmtAlert->execute([
                                ':sid'  => $sessionId,
                                ':type' => 'etape_validee',
                                ':msg'  => "Etape {$stepOrder} validee : {$currentStep['label']} ({$distVal} cm)",
                                ':sev'  => 'info'
                            ]);

                            // Mettre a jour progression G5E
                            $progress = (int)round((count($validatedSteps) / count($steps)) * 100);
                            $stmtProgress->execute([':progress' => $progress]);
                            echo "[PROGRESS] G5E → {$progress}%\n";

                            // Passer a l'etape suivante
                            $currentStep = getCurrentStep($steps, $validatedSteps);
                            $wasInZone = false;
                            $inZoneSince = null;
                            $outOfZoneCount = 0;

                            // Mettre a jour liveData immediatement pour le client
                            $liveData['current_step'] = $currentStep ? (int)$currentStep['step_order'] : null;
                            $liveData['in_zone'] = false;
                            $liveData['hold_time'] = 0;

                            if ($currentStep) {
                                echo "[JEU] Prochaine etape : {$currentStep['step_order']} — {$currentStep['label']} ({$currentStep['target_distance_cm']} cm)\n";
                            } else {
                                echo "===================================================\n";
                                echo "[VICTOIRE] Toutes les etapes validees ! Enigme reussie !\n";
                                echo "===================================================\n";

                                $stmtAlert->execute([
                                    ':sid'  => $sessionId,
                                    ':type' => 'session_terminee',
                                    ':msg'  => 'Enigme reussie ! Station reequilibree.',
                                    ':sev'  => 'info'
                                ]);

                                $stmtSession->execute([
                                    ':status' => 'reussie',
                                    ':sid'    => $sessionId
                                ]);

                                $stmtProgress->execute([':progress' => 100]);
                            }
                        }
                    } else {
                        // Lecture hors zone
                        if ($wasInZone) {
                            $outOfZoneCount++;

                            if ($outOfZoneCount > $MAX_OUT_OF_ZONE) {
                                // Vraiment sorti de la zone (pas juste du bruit)
                                echo "\n[ZONE] Objet sorti de la zone ({$distVal} cm, {$outOfZoneCount} lectures hors zone).\n";
                                $wasInZone = false;
                                $inZoneSince = null;
                                $outOfZoneCount = 0;
                            } else {
                                // Tolerer le bruit — garder le timer actif
                                $holdTime = $now - $inZoneSince;
                                $liveData['in_zone'] = true;
                                $liveData['hold_time'] = round($holdTime, 1);
                                echo "\r[BRUIT] Lecture hors zone ignoree ($outOfZoneCount/$MAX_OUT_OF_ZONE, {$distVal} cm)   ";
                            }
                        }
                    }
                }

                // --- Fichier live (avec infos jeu) ---
                file_put_contents($liveFile, json_encode($liveData));

                // --- Insertion BDD (1x/s) ---
                if ($now - $lastDbInsert >= 1.0) {
                    try {
                        $stmtInsert->execute([
                            ':sid' => $sessionId,
                            ':adc' => $adcVal,
                            ':dist' => $distVal
                        ]);
                        $readCount++;
                        $lastDbInsert = $now;
                    } catch (PDOException $e) {
                        echo "[ERREUR BDD] " . $e->getMessage() . "\n";
                    }
                }
            } else {
                echo "[RAW] {$line}\n";
            }
        }
    }
}

dio_close($serialPort);

