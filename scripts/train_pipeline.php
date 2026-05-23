<?php

/**
 * HRITIK AI - CONTINUOUS BACKGROUND TRAINING PIPELINE
 *
 * Ye script bade datasets (JSONL / TXT) ko chhote chunks mein padhkar
 * Hritik AI ko continuously train karti hai bina memory limit cross kiye.
 *
 * Run using: php scripts/train_pipeline.php --file=data.jsonl --type=jsonl
 */

require_once __DIR__ . '/../core/Bootstrap.php';

use Core\GenerativeAI\GenerativeAIAssistant;

echo "Starting Hritik AI Continuous Training Pipeline...\n";

// Parse CLI options
$options = getopt("", ["file:", "type:", "limit:"]);

$filePath = $options['file'] ?? '';
$fileType = $options['type'] ?? 'jsonl'; // text or jsonl
$limit = isset($options['limit']) ? (int)$options['limit'] : 0; // 0 means no limit

if (empty($filePath) || !file_exists($filePath)) {
    echo "Error: Please provide a valid file path.\n";
    echo "Usage: php train_pipeline.php --file=dataset.jsonl --type=jsonl\n";
    exit(1);
}

echo "Dataset: $filePath ($fileType)\n";
echo "Initializing AI Core...\n";

$ai = new GenerativeAIAssistant();
$corpusPath = __DIR__ . '/../core/GenerativeAI/neural_corpus.txt';

echo "Opening file stream...\n";
$handle = fopen($filePath, "r");

if (!$handle) {
    echo "Error: Could not open file.\n";
    exit(1);
}

$lineCount = 0;
$processedCount = 0;
$batchText = "";
$batchSize = 100; // Save to corpus every 100 lines

while (($line = fgets($handle)) !== false) {
    $lineCount++;
    $textToLearn = "";

    if ($fileType === 'jsonl') {
        $data = json_decode($line, true);
        if ($data) {
            // Extract text based on common HF dataset structures
            // Examples: 'text', 'conversations', 'content'
            if (isset($data['text'])) {
                $textToLearn = $data['text'];
            } elseif (isset($data['content'])) {
                $textToLearn = $data['content'];
            } elseif (isset($data['conversations']) && is_array($data['conversations'])) {
                foreach ($data['conversations'] as $conv) {
                    if (isset($conv['value'])) {
                        $textToLearn .= $conv['value'] . " ";
                    }
                }
            } else {
                // Try to find any string values if unknown structure
                foreach ($data as $key => $val) {
                    if (is_string($val)) {
                        $textToLearn .= $val . " ";
                    }
                }
            }
        }
    } else {
        // Plain text
        $textToLearn = trim($line);
    }

    $textToLearn = trim($textToLearn);
    if (!empty($textToLearn)) {
        // Feed directly to memory for live learning
        $ai->learn($textToLearn);

        // Append to batch for saving to corpus
        $batchText .= $textToLearn . PHP_EOL;
        $processedCount++;

        // Save batch to corpus periodically to avoid I/O overhead
        if ($processedCount % $batchSize === 0) {
            file_put_contents($corpusPath, $batchText, FILE_APPEND | LOCK_EX);
            $batchText = ""; // reset batch
            echo "Processed & saved $processedCount lines/chunks...\n";

            // Optional memory cleanup
            gc_collect_cycles();
        }
    }

    if ($limit > 0 && $lineCount >= $limit) {
        echo "Limit of $limit reached. Stopping.\n";
        break;
    }
}

// Save any remaining text
if (!empty($batchText)) {
    file_put_contents($corpusPath, $batchText, FILE_APPEND | LOCK_EX);
    echo "Processed & saved remaining $processedCount lines/chunks...\n";
}

fclose($handle);
echo "Training Pipeline completed successfully!\n";
echo "Total lines processed: $processedCount / $lineCount\n";
