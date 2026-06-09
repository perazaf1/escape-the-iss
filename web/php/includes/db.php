<?php
/**
 * ISS Escape Game G5E — Connexion BDD
 * MariaDB partagée sur node.solyzon.com:3307
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $configFile = __DIR__ . '/db.config.php';
        if (!file_exists($configFile)) {
            die('[ERREUR] Fichier db.config.php manquant. Copier db.config.example.php vers db.config.php et remplir les identifiants.');
        }
        $cfg = require $configFile;
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]);
    }
    return $pdo;
}
