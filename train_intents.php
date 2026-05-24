<?php
/**
 * HRITIK AI - INTENT CLASSIFIER TRAINER
 *
 * CSV format: text,intent
 */

require_once __DIR__ . '/core/Bootstrap.php';

use Core\NLP\Intents\TrainableIntentClassifier;

$options = getopt('', ['file::', 'epochs::', 'test::', 'help']);
if (isset($options['help'])) {
    echo "Usage:\n";
    echo "  H:\\xampp\\php\\php.exe train_intents.php --file=storage/datasets/hinglish_intents.csv --epochs=3\n";
    echo "  H:\\xampp\\php\\php.exe train_intents.php --test=\"tum kaun ho\"\n";
    exit(0);
}

$modelPath = __DIR__ . '/storage/models/intent_classifier.json';

if (isset($options['test'])) {
    $model = TrainableIntentClassifier::load($modelPath);
    if (!$model) {
        echo "No trained model found. Train first.\n";
        exit(1);
    }
    echo json_encode($model->predict((string)$options['test']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

$file = (string)($options['file'] ?? (__DIR__ . '/storage/datasets/hinglish_intents.csv'));
if (!preg_match('/^[a-zA-Z]:[\\\\\/]/', $file) && !str_starts_with($file, DIRECTORY_SEPARATOR)) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . $file;
}
if (!is_file($file)) {
    echo "Dataset not found: {$file}\n";
    exit(1);
}

$epochs = max(1, (int)($options['epochs'] ?? 1));
$model = new TrainableIntentClassifier();
$rows = loadIntentRows($file);

for ($epoch = 1; $epoch <= $epochs; $epoch++) {
    foreach ($rows as $row) {
        $model->train($row['text'], $row['intent']);
    }
    echo "Epoch {$epoch}/{$epochs} trained on " . count($rows) . " rows.\n";
}

$model->save($modelPath);
echo "Saved model: {$modelPath}\n";
echo "Try: H:\\xampp\\php\\php.exe train_intents.php --test=\"tum kaun ho\"\n";

function loadIntentRows(string $file): array {
    $handle = fopen($file, 'r');
    if (!$handle) {
        return [];
    }

    $header = fgetcsv($handle);
    $textIndex = 0;
    $intentIndex = 1;
    if (is_array($header)) {
        $lower = array_map(fn($v) => strtolower(trim((string)$v)), $header);
        $textIndex = array_search('text', $lower, true);
        if ($textIndex === false) {
            $textIndex = array_search('input', $lower, true);
        }
        $intentIndex = array_search('intent', $lower, true);
        if ($intentIndex === false) {
            $intentIndex = array_search('label', $lower, true);
        }
        $textIndex = $textIndex === false ? 0 : (int)$textIndex;
        $intentIndex = $intentIndex === false ? 1 : (int)$intentIndex;
    }

    $rows = [];
    while (($data = fgetcsv($handle)) !== false) {
        $text = trim((string)($data[$textIndex] ?? ''));
        $intent = trim((string)($data[$intentIndex] ?? ''));
        if ($text !== '' && $intent !== '') {
            $rows[] = ['text' => $text, 'intent' => $intent];
        }
    }
    fclose($handle);
    return $rows;
}
