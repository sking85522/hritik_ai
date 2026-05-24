<?php
/**
 * HRITIK AI - REMOTE DB API TEST
 *
 * Sends one SQL query through online_db.php, never direct mysqli.
 */

require_once __DIR__ . '/online_db.php';

$query = PHP_SAPI === 'cli'
    ? trim(implode(' ', array_slice($argv, 1)))
    : trim((string)($_GET['q'] ?? ''));

if ($query === '') {
    $query = 'SELECT COUNT(*) as total FROM neural_memory';
}

putenv('HRITIK_REMOTE_DB_STRICT=1');
$started = microtime(true);
$result = $db->query($query);
$elapsed = round(microtime(true) - $started, 3);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json');
}

echo json_encode([
    'query' => $query,
    'elapsed_seconds' => $elapsed,
    'result' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
