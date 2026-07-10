<?php
namespace PHPTorch\NN;

abstract class Module {
    public function __invoke(...$args) {
        return $this->forward(...$args);
    }

    abstract public function forward(...$args): \PHPTorch\Tensor;

    public function parameters(): array {
        $params = [];
        $props = get_object_vars($this);
        foreach ($props as $prop) {
            if ($prop instanceof \PHPTorch\Tensor && $prop->requires_grad) {
                $params[] = $prop;
            } elseif ($prop instanceof Module) {
                // Bolt Optimization: Replaced O(N^2) array_merge in loop with O(1) foreach append
                foreach ($prop->parameters() as $p) {
                    $params[] = $p;
                }
            } elseif (is_array($prop)) {
                foreach ($prop as $item) {
                     if ($item instanceof Module) {
                         // Bolt Optimization: Replaced O(N^2) array_merge in loop with O(1) foreach append
                         foreach ($item->parameters() as $p) {
                             $params[] = $p;
                         }
                     }
                }
            }
        }
        return $params;
    }
}
