-- ============================================================
-- Escape Game ISS — G5E Salle de Stockage
-- Base de données : escapegame_G5B (MariaDB, node.solyzon.com:3307)
-- Toutes nos tables sont préfixées g5e_
-- ============================================================

-- 1. UTILISATEURS (Auth)
CREATE TABLE IF NOT EXISTS g5e_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'password_hash via password_hash()',
    role ENUM('joueur', 'game_master') NOT NULL DEFAULT 'joueur',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. SESSIONS DE JEU (Escape Game)
CREATE TABLE IF NOT EXISTS g5e_game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    started_by INT NOT NULL COMMENT 'ID du Game Master qui lance la session',
    status ENUM('en_cours', 'reussie', 'echouee', 'annulee') NOT NULL DEFAULT 'en_cours',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME DEFAULT NULL,
    duration_seconds INT DEFAULT NULL COMMENT 'Durée totale en secondes',
    FOREIGN KEY (started_by) REFERENCES g5e_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ÉTAPES DE L'ÉNIGME (distances cibles pour chaque objet)
CREATE TABLE IF NOT EXISTS g5e_enigme_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_order TINYINT UNSIGNED NOT NULL COMMENT 'Ordre de l étape (1, 2, 3...)',
    label VARCHAR(100) NOT NULL COMMENT 'Nom de l objet ou de l étape (ex: Caisse Oxygène)',
    target_distance_cm SMALLINT UNSIGNED NOT NULL COMMENT 'Distance cible en cm',
    tolerance_cm TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT 'Marge d erreur acceptée en cm',
    hint_text VARCHAR(255) DEFAULT NULL COMMENT 'Indice affiché aux joueurs',
    unlocked TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Enigme résolue côté web (0=verrouillé, 1=déverrouillé)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration : ajouter la colonne unlocked si la table existe déjà
-- ALTER TABLE g5e_enigme_steps ADD COLUMN IF NOT EXISTS unlocked TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Enigme résolue côté web (0=verrouillé, 1=déverrouillé)';

-- Données initiales des étapes (ajustables)
-- Ordre non croissant volontaire : 35 → 20 → 50 cm
INSERT INTO g5e_enigme_steps (step_order, label, target_distance_cm, tolerance_cm, hint_text) VALUES
(1, 'Réservoir O2 auxiliaire', 35, 4, 'L oxygène de secours, ni trop près ni trop loin.'),
(2, 'Caisse de rations', 20, 4, 'La nourriture doit être à portée de main...'),
(3, 'Module de communication', 50, 4, 'Les transmissions nécessitent de l espace.');

-- 4. LOGS DU CAPTEUR (historique des mesures)
CREATE TABLE IF NOT EXISTS g5e_capteur_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT DEFAULT NULL COMMENT 'Lié à une session de jeu (NULL = hors session)',
    adc_value SMALLINT UNSIGNED NOT NULL COMMENT 'Valeur brute ADC 12-bit (0-4095)',
    distance_cm SMALLINT UNSIGNED NOT NULL COMMENT 'Distance calculée en cm (10-80)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES g5e_game_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_g5e_logs_date ON g5e_capteur_logs (created_at);
CREATE INDEX idx_g5e_logs_session ON g5e_capteur_logs (session_id);

-- 5. ALERTES (journal des événements critiques)
CREATE TABLE IF NOT EXISTS g5e_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT DEFAULT NULL,
    type ENUM('desequilibre', 'seuil_critique', 'capteur_hors_ligne', 'etape_validee', 'etape_echouee', 'session_terminee') NOT NULL,
    message VARCHAR(255) NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES g5e_game_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_g5e_alerts_date ON g5e_alerts (created_at);

-- 6. COMMANDES ACTIONNEUR (contrôle à distance par le Game Master)
CREATE TABLE IF NOT EXISTS g5e_actuator_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sent_by INT NOT NULL COMMENT 'ID du Game Master',
    command ENUM('lock', 'unlock', 'buzzer_on', 'buzzer_off', 'reset', 'hint') NOT NULL,
    status ENUM('pending', 'sent', 'executed', 'failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (sent_by) REFERENCES g5e_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
