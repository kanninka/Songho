<?php
/* =========================================================
   POST /api/join_game.php
   Body JSON : { "code": "XXXXXX" }
   Rejoint une partie existante en tant que joueur Nord
   ========================================================= */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée']); exit; }

require_once __DIR__ . '/db.php';

$body = json_decode(file_get_contents('php://input'), true);
$code = isset($body['code']) ? strtoupper(trim($body['code'])) : '';

if (!$code) { http_response_code(400); echo json_encode(['error' => 'Code requis']); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM games WHERE code = :code");
$stmt->execute([':code' => $code]);
$row  = $stmt->fetch();

if (!$row)                             { http_response_code(404); echo json_encode(['error' => 'Partie introuvable']); exit; }
if ($row['session_status'] !== 'waiting') { http_response_code(400); echo json_encode(['error' => 'Partie complète ou terminée']); exit; }

$token = bin2hex(random_bytes(16));

$upd = $pdo->prepare("
    UPDATE games
    SET north_token = :north_token, session_status = 'playing', updated_at = CURRENT_TIMESTAMP
    WHERE code = :code
");
$upd->execute([':north_token' => $token, ':code' => $code]);

echo json_encode(['token' => $token, 'role' => 'north']);
