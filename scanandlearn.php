<?php
/**
 * HRITIK AI - SCAN AND LEARN
 *
 * Builds local knowledge from folders/files without any external API.
 *
 * Examples:
 *   H:\xampp\php\php.exe scanandlearn.php --path=H:\xampp\htdocs\hritik_ai --limit=200
 *   H:\xampp\php\php.exe scanandlearn.php --path=H:\ --limit=1000 --epochs=2
 */

require_once __DIR__ . '/online_db.php';
require_once __DIR__ . '/core/Bootstrap.php';

$options = getopt('', [
    'path::',
    'limit::',
    'epochs::',
    'max-file-mb::',
    'chunk-size::',
    'dry-run',
    'extensions::',
    'help',
]);

if (isset($options['help'])) {
    printHelp();
    exit(0);
}

$rootPath = realpath((string)($options['path'] ?? __DIR__));
if ($rootPath === false || !file_exists($rootPath)) {
    echo "Invalid --path. Example: --path=H:\\xampp\\htdocs\\hritik_ai\n";
    exit(1);
}

$limit = max(0, (int)($options['limit'] ?? 0));
$epochs = max(1, (int)($options['epochs'] ?? 1));
$maxFileBytes = max(1, (int)($options['max-file-mb'] ?? 2)) * 1024 * 1024;
$chunkSize = max(400, (int)($options['chunk-size'] ?? 1400));
$dryRun = isset($options['dry-run']);
$extensions = parseExtensions((string)($options['extensions'] ?? ''));

$skipDirs = array_flip([
    '.git', '.svn', '.hg', 'node_modules', 'vendor', 'cache', 'tmp', 'temp',
    'logs', 'bin', 'obj', '.idea', '.vscode', '__pycache__', 'storage\\local_db.json'
]);

$skipNames = [
    '.env', 'id_rsa', 'id_dsa', 'id_ecdsa', 'id_ed25519',
    'password', 'passwd', 'secret', 'token', 'credential', 'private_key'
];

$stats = [
    'seen' => 0,
    'learned_files' => 0,
    'chunks' => 0,
    'skipped' => 0,
    'errors' => 0,
];

echo "HRITIK AI ScanAndLearn\n";
echo "Path: {$rootPath}\n";
echo "Epochs: {$epochs} | Limit: " . ($limit ?: 'none') . " | Max file: " . (int)($maxFileBytes / 1024 / 1024) . " MB\n";
echo $dryRun ? "Mode: dry-run, no DB writes\n\n" : "Mode: learning into local DB\n\n";

for ($epoch = 1; $epoch <= $epochs; $epoch++) {
    echo "Epoch {$epoch}/{$epochs} started...\n";
    scanPath($rootPath, $extensions, $skipDirs, $skipNames, $maxFileBytes, $chunkSize, $limit, $dryRun, $stats);
    echo "Epoch {$epoch}/{$epochs} done. Chunks learned so far: {$stats['chunks']}\n\n";
    if ($limit > 0 && $stats['learned_files'] >= $limit) {
        break;
    }
}

echo "ScanAndLearn completed.\n";
echo "Files seen: {$stats['seen']}\n";
echo "Files learned: {$stats['learned_files']}\n";
echo "Chunks saved: {$stats['chunks']}\n";
echo "Skipped: {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";

function scanPath(
    string $rootPath,
    array $extensions,
    array $skipDirs,
    array $skipNames,
    int $maxFileBytes,
    int $chunkSize,
    int $limit,
    bool $dryRun,
    array &$stats
): void {
    $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($rootPath, $flags),
            function (SplFileInfo $current) use ($skipDirs, $skipNames) {
                $name = strtolower($current->getFilename());
                if ($current->isDir()) {
                    return !isset($skipDirs[$name]);
                }
                return !isSensitiveName($name, $skipNames);
            }
        )
    );

    foreach ($iterator as $file) {
        if ($limit > 0 && $stats['learned_files'] >= $limit) {
            return;
        }

        $stats['seen']++;
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (!shouldLearnFile($file, $extensions, $maxFileBytes)) {
            $stats['skipped']++;
            continue;
        }

        try {
            $text = file_get_contents($path);
            if ($text === false || !looksLikeText($text)) {
                $stats['skipped']++;
                continue;
            }

            $text = cleanText($text);
            if (strlen($text) < 80) {
                $stats['skipped']++;
                continue;
            }

            $chunks = chunkText($text, $chunkSize);
            $saved = 0;
            foreach ($chunks as $index => $chunk) {
                if ($dryRun) {
                    $saved++;
                    continue;
                }
                if (saveKnowledgeChunk($path, $index, $chunk)) {
                    $saved++;
                }
            }

            if ($saved > 0) {
                $stats['learned_files']++;
                $stats['chunks'] += $saved;
                echo "Learned {$saved} chunks: {$path}\n";
            }
        } catch (Throwable $e) {
            $stats['errors']++;
            echo "Error: {$path} :: {$e->getMessage()}\n";
        }
    }
}

function shouldLearnFile(SplFileInfo $file, array $extensions, int $maxFileBytes): bool {
    if ($file->getSize() <= 0 || $file->getSize() > $maxFileBytes) {
        return false;
    }

    $ext = strtolower($file->getExtension());
    if ($extensions) {
        return isset($extensions[$ext]);
    }

    $default = [
        'txt' => true, 'md' => true, 'php' => true, 'js' => true, 'ts' => true,
        'html' => true, 'css' => true, 'json' => true, 'xml' => true, 'csv' => true,
        'yml' => true, 'yaml' => true, 'sql' => true, 'py' => true, 'java' => true,
        'c' => true, 'cpp' => true, 'h' => true, 'cs' => true, 'bat' => true,
        'ps1' => true, 'log' => true, 'ini' => true, 'conf' => true
    ];

    return isset($default[$ext]);
}

function isSensitiveName(string $name, array $skipNames): bool {
    foreach ($skipNames as $needle) {
        if (str_contains($name, $needle)) {
            return true;
        }
    }
    return false;
}

function looksLikeText(string $bytes): bool {
    $sample = substr($bytes, 0, 4096);
    return !str_contains($sample, "\0");
}

function cleanText(string $text): string {
    $text = preg_replace('/^\xEF\xBB\xBF/u', '', $text);
    $text = preg_replace('/[ \t]+/', ' ', (string)$text);
    $text = preg_replace('/\R{3,}/', "\n\n", (string)$text);
    return trim((string)$text);
}

function chunkText(string $text, int $chunkSize): array {
    $chunks = [];
    $length = strlen($text);
    $offset = 0;
    $overlap = min(180, (int)($chunkSize * 0.15));

    while ($offset < $length) {
        $rawChunk = substr($text, $offset, $chunkSize);
        $chunk = $rawChunk;
        $lastBreak = max(strrpos($chunk, "\n") ?: 0, strrpos($chunk, '. ') ?: 0);
        if ($lastBreak > 300) {
            $chunk = substr($chunk, 0, $lastBreak + 1);
        }
        $chunk = trim($chunk);
        if ($chunk !== '') {
            $chunks[] = $chunk;
        }
        $advance = strlen($rawChunk);
        if ($advance < $chunkSize) {
            break;
        }
        $offset += max(1, $advance - $overlap);
    }

    return $chunks;
}

function saveKnowledgeChunk(string $path, int $index, string $chunk): bool {
    global $db;

    $tokens = tokenizeForTags($path . ' ' . $chunk);
    $tags = array_slice(array_keys($tokens), 0, 30);
    $key = basename($path) . " chunk " . ($index + 1) . " " . implode(' ', array_slice($tags, 0, 10));
    $answer = "Source: {$path}\n\n" . $chunk;

    $safeKey = addslashes($key);
    $safeAnswer = addslashes($answer);
    $safeTags = addslashes(json_encode($tags));
    $safeSource = addslashes('scanandlearn:' . $path);

    $sql = "INSERT INTO neural_knowledge (category, sub_category, k_key, k_value, quality_score, tags_json) " .
           "VALUES ('verified_qa', '{$safeSource}', '{$safeKey}', '{$safeAnswer}', 0.72, '{$safeTags}')";
    $res = $db->query($sql);
    return ($res['status'] ?? '') === 'success';
}

function tokenizeForTags(string $text): array {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9_+#.]+/i', ' ', (string)$text);
    $parts = preg_split('/\s+/', (string)$text) ?: [];
    $stop = array_flip(['the', 'and', 'for', 'with', 'this', 'that', 'from', 'hai', 'kya', 'are', 'you']);
    $tokens = [];

    foreach ($parts as $part) {
        if (strlen($part) < 3 || isset($stop[$part])) {
            continue;
        }
        $tokens[$part] = ($tokens[$part] ?? 0) + 1;
    }

    arsort($tokens);
    return $tokens;
}

function parseExtensions(string $csv): array {
    if (trim($csv) === '') {
        return [];
    }

    $result = [];
    foreach (explode(',', $csv) as $ext) {
        $ext = strtolower(trim($ext, " .\t\n\r\0\x0B"));
        if ($ext !== '') {
            $result[$ext] = true;
        }
    }
    return $result;
}

function printHelp(): void {
    echo "Usage:\n";
    echo "  H:\\xampp\\php\\php.exe scanandlearn.php --path=H:\\ --limit=1000\n\n";
    echo "Options:\n";
    echo "  --path=PATH           Folder/file root to scan. Default: current project.\n";
    echo "  --limit=N             Max learned files. Default: no limit.\n";
    echo "  --epochs=N            Repeat scan N times. Default: 1.\n";
    echo "  --max-file-mb=N       Skip files larger than N MB. Default: 2.\n";
    echo "  --chunk-size=N        Characters per knowledge chunk. Default: 1400.\n";
    echo "  --extensions=a,b,c    Only learn these extensions.\n";
    echo "  --dry-run             Scan without writing to DB.\n";
}
