<?php
namespace Core\DL\Loss;

/**
 * HRITIK AI - NEURAL LOSS FUNCTIONS
 * Calculates the mathematical "error" between prediction and actual target.
 */
class LossFunctions {
    
    public static function mse(array $predicted, array $target): float {
        $error = 0;
        foreach ($predicted as $i => $p) {
            $error += pow($p - ($target[$i] ?? 0), 2);
        }
        return $error / (count($predicted) ?: 1);
    }

    /**
     * Cross-Entropy Loss - Standard for classification and LLMs.
     */
    public static function crossEntropy(array $predicted, array $target): float {
        $loss = 0;
        foreach ($predicted as $i => $p) {
            $p = max(min($p, 1 - 1e-15), 1e-15); // Prevent log(0)
            $loss += ($target[$i] ?? 0) * log($p);
        }
        return -$loss;
    }
}
