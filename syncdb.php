<?php
/**
 * HRITIK AI - REMOTE DB QUEUE SYNC
 *
 * Retries SQL writes queued by online_db.php when the remote API was down.
 */

require_once __DIR__ . '/online_db.php';

$queuePath = __DIR__ . '/storage/remote_db_queue.json';
if (!is_file($queuePath)) {
    echo "No remote DB queue found.\n";
    exit(0);
}

$queue = json_decode((string)file_get_contents($queuePath), true);
if (!is_array($queue) || empty($queue)) {
    echo "Remote DB queue is empty.\n";
    exit(0);
}

putenv('HRITIK_REMOTE_DB_STRICT=1');

$remaining = [];
$synced = 0;
$failed = 0;

foreach ($queue as $item) {
    $sql = (string)($item['sql'] ?? '');
    if ($sql === '') {
        continue;
    }

    $res = $db->query($sql);
    if (($res['status'] ?? '') === 'success') {
        $synced++;
        echo "Synced: " . ($item['hash'] ?? hash('sha256', $sql)) . "\n";
    } else {
        $failed++;
        $item['last_error'] = $res['message'] ?? 'unknown error';
        $item['last_attempt_at'] = date('c');
        $remaining[] = $item;
        echo "Failed: " . ($item['last_error'] ?? 'unknown error') . "\n";
    }
}

file_put_contents($queuePath, json_encode($remaining, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Done. Synced {$synced}, failed {$failed}, remaining " . count($remaining) . ".\n";
