<?php
/* =========================================================
   MOTEUR DE JEU SONGHO — Traduit du TypeScript
   ========================================================= */

/* ---------- DONNÉES DE BASE ---------- */

$CYCLE = [
    ['player' => 'north', 'pitIndex' => 0],
    ['player' => 'north', 'pitIndex' => 1],
    ['player' => 'north', 'pitIndex' => 2],
    ['player' => 'north', 'pitIndex' => 3],
    ['player' => 'north', 'pitIndex' => 4],
    ['player' => 'north', 'pitIndex' => 5],
    ['player' => 'north', 'pitIndex' => 6],
    ['player' => 'south', 'pitIndex' => 6],
    ['player' => 'south', 'pitIndex' => 5],
    ['player' => 'south', 'pitIndex' => 4],
    ['player' => 'south', 'pitIndex' => 3],
    ['player' => 'south', 'pitIndex' => 2],
    ['player' => 'south', 'pitIndex' => 1],
    ['player' => 'south', 'pitIndex' => 0],
];

/* ---------- UTILITAIRES ÉTAT ---------- */

function createGame(string $startingPlayer = 'south'): array {
    return [
        'board' => [
            'north' => [5, 5, 5, 5, 5, 5, 5],
            'south' => [5, 5, 5, 5, 5, 5, 5],
        ],
        'scores'        => ['north' => 0, 'south' => 0],
        'currentPlayer' => $startingPlayer,
        'status'        => 'playing',
        'winner'        => null,
        'reason'        => null,
        'moveNumber'    => 0,
        'history'       => [],
    ];
}

function cloneState(array $state): array {
    return [
        'board' => [
            'north' => $state['board']['north'],
            'south' => $state['board']['south'],
        ],
        'scores'        => $state['scores'],
        'currentPlayer' => $state['currentPlayer'],
        'status'        => $state['status'],
        'winner'        => $state['winner'],
        'reason'        => $state['reason'],
        'moveNumber'    => $state['moveNumber'],
        'history'       => $state['history'],
    ];
}

function sumArr(array $values): int {
    return array_sum($values);
}

function boardSeeds(array $state): int {
    return sumArr($state['board']['north']) + sumArr($state['board']['south']);
}

function other(string $player): string {
    return $player === 'north' ? 'south' : 'north';
}

/* ---------- COORDONNÉES ---------- */

function cycleIndexOf(array $pos): int {
    global $CYCLE;
    foreach ($CYCLE as $i => $c) {
        if ($c['player'] === $pos['player'] && $c['pitIndex'] === $pos['pitIndex']) {
            return $i;
        }
    }
    return -1;
}

function nextPositionsAfter(array $source): array {
    global $CYCLE;
    $start     = cycleIndexOf($source);
    $positions = [];
    $len       = count($CYCLE);
    for ($step = 1; $step <= 13; $step++) {
        $index       = ($start + $step) % $len;
        $positions[] = $CYCLE[$index];
    }
    return $positions;
}

function attackPit(string $player): array {
    return $player === 'north'
        ? ['player' => 'north', 'pitIndex' => 6]
        : ['player' => 'south', 'pitIndex' => 0];
}

function opponentFirstPit(string $player): array {
    return $player === 'north'
        ? ['player' => 'south', 'pitIndex' => 6]
        : ['player' => 'north', 'pitIndex' => 0];
}

function opponentPath(string $player): array {
    if ($player === 'north') {
        return [
            ['player' => 'south', 'pitIndex' => 6],
            ['player' => 'south', 'pitIndex' => 5],
            ['player' => 'south', 'pitIndex' => 4],
            ['player' => 'south', 'pitIndex' => 3],
            ['player' => 'south', 'pitIndex' => 2],
            ['player' => 'south', 'pitIndex' => 1],
            ['player' => 'south', 'pitIndex' => 0],
        ];
    }
    return [
        ['player' => 'north', 'pitIndex' => 0],
        ['player' => 'north', 'pitIndex' => 1],
        ['player' => 'north', 'pitIndex' => 2],
        ['player' => 'north', 'pitIndex' => 3],
        ['player' => 'north', 'pitIndex' => 4],
        ['player' => 'north', 'pitIndex' => 5],
        ['player' => 'north', 'pitIndex' => 6],
    ];
}

function samePos(array $a, array $b): bool {
    return $a['player'] === $b['player'] && $a['pitIndex'] === $b['pitIndex'];
}

function isOpponentPit(string $player, array $position): bool {
    return $position['player'] === other($player);
}

/* ---------- SEMIS ---------- */

function sowNormal(array &$state, string $player, int $pitIndex): array {
    $seeds  = $state['board'][$player][$pitIndex];
    $source = ['player' => $player, 'pitIndex' => $pitIndex];
    $visited = [];

    $state['board'][$player][$pitIndex] = 0;
    $path = nextPositionsAfter($source);

    for ($i = 0; $i < $seeds; $i++) {
        $pos = $path[$i];
        $state['board'][$pos['player']][$pos['pitIndex']] += 1;
        $visited[] = $pos;
    }

    return [
        'visited'        => $visited,
        'lastPosition'   => $visited[count($visited) - 1],
        'specialCapture' => 0,
    ];
}

function sowGranary(array &$state, string $player, int $pitIndex): array {
    $seeds     = $state['board'][$player][$pitIndex];
    $source    = ['player' => $player, 'pitIndex' => $pitIndex];
    $visited   = [];
    $remaining = $seeds;
    $specialCapture = 0;

    $state['board'][$player][$pitIndex] = 0;

    // Premier tour complet du cycle (13 cases max)
    foreach (nextPositionsAfter($source) as $pos) {
        $state['board'][$pos['player']][$pos['pitIndex']] += 1;
        $visited[] = $pos;
        $remaining--;
    }

    // Graines restantes : on tourne sur le camp adverse
    $path      = opponentPath($player);
    $firstPit  = opponentFirstPit($player);
    $pathLen   = count($path);

    for ($i = 0; $i < $remaining; $i++) {
        $pos       = $path[$i % $pathLen];
        $isLast    = ($i === $remaining - 1);
        $isProtected = samePos($pos, $firstPit);

        if ($isLast && $isProtected) {
            $specialCapture += 1;
            $visited[] = $pos;
            continue;
        }

        $state['board'][$pos['player']][$pos['pitIndex']] += 1;
        $visited[] = $pos;
    }

    return [
        'visited'        => $visited,
        'lastPosition'   => $visited[count($visited) - 1],
        'specialCapture' => $specialCapture,
    ];
}

function sow(array &$state, string $player, int $pitIndex): array {
    $seeds = $state['board'][$player][$pitIndex];
    if ($seeds <= 0) {
        throw new Exception("La case choisie est vide.");
    }
    if ($seeds <= 13) {
        return sowNormal($state, $player, $pitIndex);
    }
    return sowGranary($state, $player, $pitIndex);
}

/* ---------- CAPTURES ---------- */

function isCaptureValue(int $count): bool {
    return $count === 2 || $count === 3 || $count === 4;
}

function canStartCapture(array $state, string $player, array $lastPos): bool {
    if (!isOpponentPit($player, $lastPos)) return false;
    if (samePos($lastPos, opponentFirstPit($player))) return false;
    $count = $state['board'][$lastPos['player']][$lastPos['pitIndex']];
    return isCaptureValue($count);
}

function captureChainPositions(array $state, string $player, array $lastPos): array {
    $path      = opponentPath($player);
    $lastIndex = -1;
    foreach ($path as $i => $p) {
        if (samePos($p, $lastPos)) { $lastIndex = $i; break; }
    }
    if ($lastIndex <= 0) return [];

    $captured = [];
    for ($index = $lastIndex; $index >= 0; $index--) {
        $pos   = $path[$index];
        $count = $state['board'][$pos['player']][$pos['pitIndex']];
        if (!isCaptureValue($count)) break;
        $captured[] = ['player' => $pos['player'], 'pitIndex' => $pos['pitIndex'], 'seeds' => $count];
    }
    return $captured;
}

function wouldEmptyOpponent(array $state, string $player, array $captureList): bool {
    $opponent  = other($player);
    $remaining = $state['board'][$opponent];
    foreach ($captureList as $c) {
        $remaining[$c['pitIndex']] -= $c['seeds'];
    }
    return array_sum($remaining) === 0;
}

function resolveCaptures(array &$state, string $player, array $sowingResult): array {
    if ($sowingResult['specialCapture'] > 0) {
        $state['scores'][$player] += $sowingResult['specialCapture'];
        return ['captured' => $sowingResult['specialCapture'], 'type' => 'special-granary'];
    }

    $last = $sowingResult['lastPosition'];
    if (!canStartCapture($state, $player, $last)) {
        return ['captured' => 0, 'type' => 'none'];
    }

    $captureList = captureChainPositions($state, $player, $last);
    if (empty($captureList)) return ['captured' => 0, 'type' => 'none'];

    if (wouldEmptyOpponent($state, $player, $captureList)) {
        return ['captured' => 0, 'type' => 'none', 'cancelledBecauseStarvation' => true];
    }

    $total = 0;
    foreach ($captureList as $c) {
        $state['board'][$c['player']][$c['pitIndex']] -= $c['seeds'];
        $total += $c['seeds'];
    }
    $state['scores'][$player] += $total;

    return [
        'captured' => $total,
        'type'     => ($total > 0 && count($captureList) > 1) ? 'chain' : 'normal',
    ];
}

/* ---------- COUPS LÉGAUX ---------- */

function opponentCampIsEmpty(array $state, string $player): bool {
    return sumArr($state['board'][other($player)]) === 0;
}

function isAttackPitMove(string $player, int $pitIndex): bool {
    $attack = attackPit($player);
    return $attack['player'] === $player && $attack['pitIndex'] === $pitIndex;
}

function isForbiddenAttackMove(array $state, string $player, int $pitIndex): bool {
    if (!isAttackPitMove($player, $pitIndex)) return false;
    $seeds = $state['board'][$player][$pitIndex];
    if ($seeds === 1) return true;
    if ($seeds === 2) {
        $sim    = cloneState($state);
        $sowing = sowNormal($sim, $player, $pitIndex);
        return !canStartCapture($sim, $player, $sowing['lastPosition']);
    }
    return false;
}

function countDeliveredToOpponent(array $state, string $player, int $pitIndex): int {
    $sim    = cloneState($state);
    $before = sumArr($sim['board'][other($player)]);
    sow($sim, $player, $pitIndex);
    $after  = sumArr($sim['board'][other($player)]);
    return $after - $before;
}

function getSolidarityMoves(array $state, string $player): array {
    $candidates = [];
    for ($i = 0; $i < 7; $i++) {
        if ($state['board'][$player][$i] > 0) {
            $candidates[] = ['player' => $player, 'pitIndex' => $i];
        }
    }

    $ordinary = array_filter($candidates, function ($m) use ($state, $player) {
        return !isForbiddenAttackMove($state, $player, $m['pitIndex']);
    });

    $enriched = array_map(function ($m) use ($state, $player) {
        return array_merge($m, ['delivered' => countDeliveredToOpponent($state, $player, $m['pitIndex'])]);
    }, array_values($ordinary));

    $atLeastSeven = array_filter($enriched, fn($m) => $m['delivered'] >= 7);
    if (!empty($atLeastSeven)) return array_values($atLeastSeven);

    $positive = array_filter($enriched, fn($m) => $m['delivered'] > 0);
    if (!empty($positive)) {
        $maxDelivered = max(array_column(array_values($positive), 'delivered'));
        return array_values(array_filter($positive, fn($m) => $m['delivered'] === $maxDelivered));
    }

    // Donation forcée
    $forced = array_filter($candidates, function ($m) use ($state, $player) {
        return isAttackPitMove($player, $m['pitIndex'])
            && in_array($state['board'][$player][$m['pitIndex']], [1, 2]);
    });
    return array_map(fn($m) => array_merge($m, ['forcedDonation' => true]), array_values($forced));
}

function getLegalMoves(array $state): array {
    $player = $state['currentPlayer'];
    if ($state['status'] !== 'playing') return [];

    if (opponentCampIsEmpty($state, $player)) {
        return getSolidarityMoves($state, $player);
    }

    $moves = [];
    for ($i = 0; $i < 7; $i++) {
        if ($state['board'][$player][$i] > 0 && !isForbiddenAttackMove($state, $player, $i)) {
            $moves[] = ['player' => $player, 'pitIndex' => $i];
        }
    }
    return $moves;
}

/* ---------- FIN DE PARTIE ---------- */

function computeWinner(array $state): string {
    if ($state['scores']['north'] >= 40) return 'north';
    if ($state['scores']['south'] >= 40) return 'south';
    if ($state['scores']['north'] > $state['scores']['south']) return 'north';
    if ($state['scores']['south'] > $state['scores']['north']) return 'south';
    return 'draw';
}

function computeWinnerStrict(array $state): string {
    if ($state['scores']['north'] >= 40) return 'north';
    if ($state['scores']['south'] >= 40) return 'south';
    return 'draw';
}

function collectRemaining(array &$state): void {
    $state['scores']['north'] += sumArr($state['board']['north']);
    $state['scores']['south'] += sumArr($state['board']['south']);
    $state['board']['north']   = [0, 0, 0, 0, 0, 0, 0];
    $state['board']['south']   = [0, 0, 0, 0, 0, 0, 0];
}

function resolveEndGameAfterMove(array &$state): void {
    if ($state['scores']['north'] >= 40 || $state['scores']['south'] >= 40) {
        $state['status'] = 'ended';
        $state['reason'] = 'score_40';
        $state['winner'] = computeWinner($state);
        return;
    }
    if (boardSeeds($state) < 10) {
        collectRemaining($state);
        $state['status'] = 'ended';
        $state['reason'] = 'low_board';
        $state['winner'] = computeWinnerStrict($state);
    }
}

function resolveEndGameBeforeTurn(array &$state): void {
    if (!empty(getLegalMoves($state))) return;
    collectRemaining($state);
    $state['status'] = 'ended';
    $state['reason'] = 'solidarity_impossible';
    $state['winner'] = computeWinnerStrict($state);
}

/* ---------- APPLIQUER UN COUP ---------- */

function applyMove(array &$state, string $player, int $pitIndex): array {
    // Validation basique
    if ($state['status'] !== 'playing') {
        return ['ok' => false, 'error' => 'La partie est terminée.'];
    }
    if ($player !== $state['currentPlayer']) {
        return ['ok' => false, 'error' => "Ce n'est pas le tour de ce joueur."];
    }
    if ($pitIndex < 0 || $pitIndex > 6) {
        return ['ok' => false, 'error' => 'Case inconnue.'];
    }
    if ($state['board'][$player][$pitIndex] <= 0) {
        return ['ok' => false, 'error' => 'La case est vide.'];
    }

    $legal = getLegalMoves($state);
    $isLegal = false;
    $forcedDonation = false;
    foreach ($legal as $m) {
        if ($m['player'] === $player && $m['pitIndex'] === $pitIndex) {
            $isLegal = true;
            $forcedDonation = !empty($m['forcedDonation']);
            break;
        }
    }
    if (!$isLegal) {
        return ['ok' => false, 'error' => 'Coup interdit par les règles.'];
    }

    if ($forcedDonation) {
        // Donation forcée : graines directement au score adverse
        $seeds = $state['board'][$player][$pitIndex];
        $state['board'][$player][$pitIndex] = 0;
        $state['scores'][other($player)] += $seeds;
    } else {
        $sowResult    = sow($state, $player, $pitIndex);
        resolveCaptures($state, $player, $sowResult);
    }

    $state['moveNumber'] += 1;

    resolveEndGameAfterMove($state);

    if ($state['status'] === 'playing') {
        $state['currentPlayer'] = other($player);
        resolveEndGameBeforeTurn($state);
    }

    return ['ok' => true];
}
