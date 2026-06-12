<?php
/* =========================================================
   GET /api/get_game.php?code=XXXXXX&token=YYYY
   Retourne l'état courant de la partie
   ========================================================= */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée']); exit; }

require_once __DIR__ . '/db.php';

$code  = isset($_GET['code'])  ? strtoupper(trim($_GET['code']))  : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$code) { http_response_code(400); echo json_encode(['error' => 'Code requis']); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM games WHERE code = :code");
$stmt->execute([':code' => $code]);
$row  = $stmt->fetch();

if (!$row) { http_response_code(404); echo json_encode(['error' => 'Partie introuvable']); exit; }

$state    = json_decode($row['state'], true);
$yourRole = null;
if ($token) {
    if ($token === $row['south_token']) $yourRole = 'south';
    elseif ($token === $row['north_token']) $yourRole = 'north';
}

echo json_encode([
    'code'              => $row['code'],
    'board'             => $state['board'],
    'scores'            => $state['scores'],
    'currentPlayer'     => $state['currentPlayer'],
    'status'            => $state['status'],
    'sessionStatus'     => $row['session_status'],
    'winner'            => $state['winner'],
    'reason'            => $state['reason'],
    'moveNumber'        => $state['moveNumber'],
    'yourRole'          => $yourRole,
    'waitingForOpponent'=> $row['session_status'] === 'waiting',
    'updatedAt'         => $row['updated_at'],
]);
