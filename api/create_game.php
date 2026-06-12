<?php
/* =========================================================
   POST /api/create_game.php
   Crée une nouvelle partie et retourne code + token (rôle Sud)
   ========================================================= */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée']); exit; }

require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/db.php';

$code  = strtoupper(bin2hex(random_bytes(3)));   // ex: "A3F9C1"
$token = bin2hex(random_bytes(16));               // 32 hex chars
$state = createGame('south');

$pdo = getDB();
$stmt = $pdo->prepare("
    INSERT INTO games (code, state, south_token, session_status)
    VALUES (:code, :state, :south_token, 'waiting')
");
$stmt->execute([
    ':code'        => $code,
    ':state'       => json_encode($state),
    ':south_token' => $token,
]);

echo json_encode([
    'code'  => $code,
    'token' => $token,
    'role'  => 'south',
]);
