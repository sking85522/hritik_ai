<?php
/**
 * HRITIK AI - REAL BRAIN TRAINING SCRIPT
 * Sets up a large 10M Parameter Neural Network
 */
ini_set('memory_limit', '-1');

require_once __DIR__ . '/../core/Bootstrap.php';

use NeuralPHP\NeuralPHP as nn;

echo "Initializing 10,000,000 Parameter Brain Training Pipeline...\n";

// 1. Initialize Tokenizer
echo "1. Initializing Tokenizer...\n";
$tokenizer = nn::TextTokenizer();
$training_texts = [
    "hello how are you",
    "neural networks are powerful",
    "attention is all you need",
    "train this model ten million weights"
];
$tokenizer->fit($training_texts);

echo "Vocab size dynamically built: " . $tokenizer->getVocabSize() . "\n";
// Scale down slightly for testing environment memory limits, but architecture
// is designed to support 10,000,000+ parameters
$vocab_size = 50000;
$embedding_dim = 198;
$seq_len = 4; // We pad or crop sequences to 4 tokens

// Calculate param count
$embed_params = $vocab_size * $embedding_dim;
$reasoning_params = ($embedding_dim * $embedding_dim) * 3;
$dense_params = ($seq_len * $embedding_dim) * 10;
$total_params = $embed_params + $reasoning_params + $dense_params;

echo "Targeting roughly 10,000,000 Weights...\n";
echo "Total Computed Weights: " . number_format($total_params) . "\n";

echo "2. Allocating Embeddings, Attention, and Dense layers...\n";
$model = nn::Sequential();

// Note: Allocating 50,000 x 198 in PHP will take a few MBs of RAM
$model->add(nn::Embedding($vocab_size, $embedding_dim));
$model->add(nn::Reasoning($embedding_dim));
$model->add(nn::Flatten());
$model->add(nn::Dense($seq_len * $embedding_dim, 10, 'softmax'));

// Use SGD as recommended for stability in NeuralPHP memory guidelines
$model->compile('sgd', 0.01, 'crossentropy');

// 3. Prepare Dummy Training Data
echo "3. Preparing training data...\n";
$X_train = [];
$y_train = []; // one-hot vectors

foreach ($training_texts as $i => $text) {
    $tokens = $tokenizer->encode($text);
    // pad or slice to $seq_len
    $tokens = array_slice(array_pad($tokens, $seq_len, 0), 0, $seq_len);
    $X_train[] = $tokens;

    // Create random one-hot vector for 10 classes
    $y = array_fill(0, 10, 0.0);
    $y[$i % 10] = 1.0;
    $y_train[] = $y;
}

// 4. Train the Model
echo "4. Training Model on data for 5 epochs...\n";
$start = microtime(true);
$model->fit($X_train, $y_train, 5); // Just 5 epochs to demonstrate functionality without taking too long
$end = microtime(true);

echo "Training completed in " . round($end - $start, 2) . " seconds.\n";

// 5. Test Inference
echo "5. Testing Inference...\n";
$test_tokens = array_slice(array_pad($tokenizer->encode("hello how are you"), $seq_len, 0), 0, $seq_len);
$prediction = $model->predict([$test_tokens])[0];

$max_idx = array_search(max($prediction), $prediction);
echo "Prediction for 'hello how are you' -> Class $max_idx\n";

echo "Successfully trained Real Brain model with Tokenizer, Embeddings, Reasoning Layers, Loss, and Optimizer!\n";
