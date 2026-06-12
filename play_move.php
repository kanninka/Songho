<?php
/* =========================================================
   POST /api/play_move.php
   Body JSON : { "code": "XXXXXX", "token": "...", "pitIndex": 3 }
   Joue un coup côté serveur et retourne le nouvel état
   ========================================================= */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'Méthode non autorisée']); exit; }

require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/db.php';

$body     = json_decode(file_get_contents('php://input'), true);
$code     = isset($body['code'])     ? strtoupper(trim($body['code']))    : '';
$token    = isset($body['token'])    ? trim($body['token'])               : '';
$pitIndex = isset($body['pitIndex']) ? (int)$body['pitIndex']             : -1;

if (!$code || !$token || $pitIndex < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'code, token et pitIndex requis']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM games WHERE code = :code");
$stmt->execute([':code' => $code]);
$row  = $stmt->fetch();

if (!$row) { http_response_code(404); echo json_encode(['error' => 'Partie introuvable']); exit; }
if ($row['session_status'] !== 'playing') {
    http_response_code(400);
    echo json_encode(['error' => "La partie n'est pas en cours"]);
    exit;
}

// Identifier le joueur d'après le token
$player = null;
if ($token === $row['south_token']) $player = 'south';
elseif ($token === $row['north_token']) $player = 'north';

if (!$player) { http_response_code(400); echo json_encode(['error' => 'Token invalide']); exit; }

$state  = json_decode($row['state'], true);
$result = applyMove($state, $player, $pitIndex);

if (!$result['ok']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

$newSessionStatus = ($state['status'] === 'ended') ? 'ended' : 'playing';

$upd = $pdo->prepare("
    UPDATE games
    SET state = :state, session_status = :session_status, updated_at = CURRENT_TIMESTAMP
    WHERE code = :code
");
$upd->execute([
    ':state'          => json_encode($state),
    ':session_status' => $newSessionStatus,
    ':code'           => $code,
]);

$yourRole = ($token === $row['south_token']) ? 'south' : 'north';

echo json_encode([
    'code'              => $row['code'],
    'board'             => $state['board'],
    'scores'            => $state['scores'],
    'currentPlayer'     => $state['currentPlayer'],
    'status'            => $state['status'],
    'sessionStatus'     => $newSessionStatus,
    'winner'            => $state['winner'],
    'reason'            => $state['reason'],
    'moveNumber'        => $state['moveNumber'],
    'yourRole'          => $yourRole,
    'waitingForOpponent'=> false,
    'updatedAt'         => date('Y-m-d H:i:s'),
]);
