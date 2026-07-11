<?php
require_once __DIR__ . '/core/Bootstrap.php';

if (file_exists(__DIR__ . '/online_db.php')) {
    require_once __DIR__ . '/online_db.php';
} else {
    class MockDB {
        public function query($sql) { return ['data' => []]; }
    }
    $db = new MockDB();
}

$prompts = [
    'Write a short story about a robot who learns emotions.',
    'Explain quantum computing in simple Hindi-English steps.',
    'Generate PHP code for a QR code generator.'
];

$core = new \Core\Engine\AgenticCore();

foreach ($prompts as $i => $prompt) {
    echo "\nRunning Prompt " . ($i + 1) . ": $prompt\n";
    $response = $core->solve($prompt);
    echo "Response:\n$response\n---------------------------------------\n";
}
