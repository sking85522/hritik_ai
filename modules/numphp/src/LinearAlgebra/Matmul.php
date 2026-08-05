<?php

namespace NumPHP\LinearAlgebra;

use NumPHP\Core\NDArray;

class Matmul
{
    public static function matmul(NDArray $a, NDArray $b): NDArray
    {
        $shapeA = $a->getShape();
        $shapeB = $b->getShape();

        $aData = $a->getData();
        $bData = $b->getData();

        // Handle 1D cases by promoting to 2D
        if (count($shapeA) === 1 && count($shapeB) === 1) {
            if ($shapeA[0] !== $shapeB[0]) {
                throw new \InvalidArgumentException(
                    sprintf("Shapes %s and %s not aligned: %d (dim 0) != %d (dim 0)",
                        json_encode($shapeA), json_encode($shapeB), $shapeA[0], $shapeB[0])
                );
            }
            $sum = 0;
            for ($i = 0; $i < $shapeA[0]; $i++) {
                $sum += $aData[$i] * $bData[$i];
            }
            return new NDArray([$sum]);
        }

        if (count($shapeA) === 1) {
            // Treat A as row vector (1, n)
            $a = new NDArray([$aData]);
            $shapeA = $a->getShape();
        }
        if (count($shapeB) === 1) {
            // Treat B as column vector (n, 1)
            $col = [];
            foreach ($bData as $val) {
                $col[] = [$val];
            }
            $b = new NDArray($col);
            $shapeB = $b->getShape();
        }

        if (count($shapeA) !== 2 || count($shapeB) !== 2) {
            throw new \InvalidArgumentException("Matmul currently supports 2D arrays only.");
        }

        if ($shapeA[1] !== $shapeB[0]) {
            throw new \InvalidArgumentException(
                sprintf("Shapes %s and %s not aligned: %d (dim 1) != %d (dim 0)",
                json_encode($shapeA), json_encode($shapeB), $shapeA[1], $shapeB[0])
            );
        }

        $dataA = $a->getData();
        $dataB = $b->getData();

        $m = $shapeA[0]; // Rows of A
        $k = $shapeA[1]; // Cols of A (and Rows of B)
        $n = $shapeB[1]; // Cols of B

        $result = [];

        // Bolt Optimization: Use i-k-j loop order to improve cache locality
        // and avoid O(N^3) array column lookups without needing to allocate
        // a transposed matrix, which saves a lot of memory in PHP.

        // Initialize result matrix
        for ($i = 0; $i < $m; $i++) {
            $result[$i] = array_fill(0, $n, 0);
        }

        // O(N^3) Multiplication with i-k-j loop order
        for ($i = 0; $i < $m; $i++) {
            $rowA = $dataA[$i];
            for ($l = 0; $l < $k; $l++) {
                $a_il = $rowA[$l];
                $rowB = $dataB[$l];
                for ($j = 0; $j < $n; $j++) {
                    $result[$i][$j] += $a_il * $rowB[$j];
                }
            }
        }

        return new NDArray($result);
    }
}
