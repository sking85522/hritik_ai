<?php
/**
 * HRITIK AI - DATASET PROCESSOR
 *
 * Imports hinglish_conversations.csv into neural_memory using online_db.php.
 * If HRITIK_REMOTE_DB_URL and HRITIK_REMOTE_DB_KEY are set, records go to the
 * remote API. Otherwise they are saved into storage/local_db.json.
 *
 * CLI:
 *   H:\xampp\php\php.exe datasetrun.php --file=hinglish_conversations.csv --limit=1000
 */

require_once __DIR__ . '/online_db.php';

$isCli = PHP_SAPI === 'cli';
$options = $isCli ? getopt('', ['file::', 'limit::', 'batch-size::', 'help']) : [];

if (isset($options['help'])) {
    printDatasetHelp();
    exit(0);
}

$csvFile = (string)($options['file'] ?? ($_GET['file'] ?? (__DIR__ . '/hinglish_conversations.csv')));
if (!preg_match('/^[a-zA-Z]:[\\\\\/]/', $csvFile) && !str_starts_with($csvFile, DIRECTORY_SEPARATOR)) {
    $csvFile = __DIR__ . DIRECTORY_SEPARATOR . $csvFile;
}

$limit = max(0, (int)($options['limit'] ?? ($_GET['limit'] ?? 0)));
$batchSize = max(1, (int)($options['batch-size'] ?? ($_GET['batch_size'] ?? 1000)));

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

outLine("Hritik AI - Dataset Processing Started");
outLine("File: {$csvFile}");
outLine("Target: remote DB API with local fallback/queue");

if (!is_file($csvFile)) {
    outLine("Error: CSV file not found.");
    exit(1);
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    outLine("Error: Could not open CSV file.");
    exit(1);
}

$header = fgetcsv($handle);
$promptIndex = 0;
$responseIndex = 1;
if (is_array($header)) {
    $lower = array_map(fn($v) => strtolower(trim((string)$v)), $header);
    $promptIndex = array_search('input', $lower, true);
    if ($promptIndex === false) {
        $promptIndex = array_search('prompt', $lower, true);
    }
    $responseIndex = array_search('output', $lower, true);
    if ($responseIndex === false) {
        $responseIndex = array_search('response', $lower, true);
    }
    $promptIndex = $promptIndex === false ? 0 : (int)$promptIndex;
    $responseIndex = $responseIndex === false ? 1 : (int)$responseIndex;
}

$count = 0;
$inserted = 0;
$failed = 0;

while (($data = fgetcsv($handle)) !== false) {
    $prompt = trim((string)($data[$promptIndex] ?? ''));
    $response = trim((string)($data[$responseIndex] ?? ''));

    if ($prompt !== '' && $response !== '') {
        $sql = "INSERT INTO neural_memory (prompt, response) VALUES ('" .
               addslashes($prompt) . "', '" . addslashes($response) . "')";
        $res = $db->query($sql);
        if (($res['status'] ?? '') === 'success') {
            $inserted++;
        } else {
            $failed++;
            if ($failed <= 5) {
                outLine("Insert failed near row {$count}: " . ($res['message'] ?? 'unknown error'));
            }
        }
    }

    $count++;
    if ($count % $batchSize === 0) {
        outLine("Progress: {$count} rows processed ({$inserted} inserted, {$failed} failed)");
    }

    if ($limit > 0 && $count >= $limit) {
        outLine("Limit {$limit} reached.");
        break;
    }
}

fclose($handle);
outLine("Done. Total {$count} rows processed. {$inserted} records added. {$failed} failed.");

function outLine(string $message): void {
    if (PHP_SAPI === 'cli') {
        echo $message . PHP_EOL;
        return;
    }

    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "<br>\n";
    @ob_flush();
    flush();
}

function printDatasetHelp(): void {
    echo "Usage:\n";
    echo "  H:\\xampp\\php\\php.exe datasetrun.php --file=hinglish_conversations.csv --limit=1000\n\n";
    echo "Environment for remote DB:\n";
    echo "  HRITIK_REMOTE_DB_URL=https://your-domain/api.php\n";
    echo "  HRITIK_REMOTE_DB_KEY=your-api-key\n";
}
