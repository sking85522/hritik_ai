<?php
require_once 'core/Bootstrap.php';
require_once 'modules/neuralphp/autoload.php';

use NeuralPHP\NeuralPHP as nn;

$X = [[0,0], [0,1], [1,0], [1,1]];
$y = [[0], [1], [1], [0]];

echo "Building XOR Model (SGD & MSE)...\n";
$model = nn::Sequential();
$model->add(nn::Dense(2, 4, 'relu'));
$model->add(nn::Dense(4, 1, 'sigmoid'));
$model->compile('sgd', 0.5, 'mse');

echo "Training XOR Model...\n";
$model->fit($X, $y, 5000);

echo "\nPredicting:\n";
foreach($X as $test_input) {
    $result = $model->predict([$test_input])[0][0];
    echo implode(',', $test_input) . " -> " . round($result, 4) . "\n";
}
