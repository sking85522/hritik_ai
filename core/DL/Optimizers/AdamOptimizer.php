<?php
namespace Core\DL\Optimizers;

/**
 * HRITIK AI - ADAM OPTIMIZER (ADAPTIVE MOMENT ESTIMATION)
 * State-of-the-art training algorithm for neural weight updates.
 */
class AdamOptimizer {
    
    private float $learningRate;
    private float $beta1 = 0.9;
    private float $beta2 = 0.999;
    private float $epsilon = 1e-8;
    
    private array $m = []; // First moment
    private array $v = []; // Second moment
    private int $t = 0;

    public function __construct(float $learningRate = 0.001) {
        $this->learningRate = $learningRate;
    }

    /**
     * Updates weights using the Adam algorithm.
     */
    public function update(array &$weights, array $gradients, string $layerId): void {
        $this->t++;
        if (!isset($this->m[$layerId])) {
            $this->m[$layerId] = array_fill(0, count($weights), 0.0);
            $this->v[$layerId] = array_fill(0, count($weights), 0.0);
        }

        foreach ($weights as $i => &$w) {
            $g = $gradients[$i] ?? 0;
            
            // Update moments
            $this->m[$layerId][$i] = $this->beta1 * $this->m[$layerId][$i] + (1 - $this->beta1) * $g;
            $this->v[$layerId][$i] = $this->beta2 * $this->v[$layerId][$i] + (1 - $this->beta2) * ($g * $g);
            
            // Bias correction
            $m_hat = $this->m[$layerId][$i] / (1 - pow($this->beta1, $this->t));
            $v_hat = $this->v[$layerId][$i] / (1 - pow($this->beta2, $this->t));
            
            // Weight update
            $w -= ($this->learningRate * $m_hat) / (sqrt($v_hat) + $this->epsilon);
        }
    }
}
