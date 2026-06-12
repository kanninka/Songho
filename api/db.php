<?php
/* =========================================================
   CONNEXION BASE DE DONNÉES — SQLite (sans configuration)
   Pour MySQL, remplacez par :
     $pdo = new PDO('mysql:host=localhost;dbname=songho', 'user', 'password');
   ========================================================= */

function getDB(): PDO {
    $dbPath = __DIR__ . '/../data/songho.sqlite';

    // Crée le dossier data/ si nécessaire
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Crée la table si elle n'existe pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS games (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            code           TEXT UNIQUE NOT NULL,
            state          TEXT NOT NULL,
            south_token    TEXT NOT NULL,
            north_token    TEXT,
            session_status TEXT NOT NULL DEFAULT 'waiting',
            created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    return $pdo;
}
