<?php

namespace NumPHP\ArrayManipulation;

use NumPHP\Core\NDArray;

class Flatten
{
    /**
     * Flattens a multidimensional NDArray into a 1D NDArray.
     *
     * ⚡ Bolt Performance Optimization:
     * Replaced the previous `recursiveFlatten` method, which used `array_merge`
     * inside a loop (O(N^2) complexity due to memory reallocation), with
     * `\NumPHP\Utils\Helpers::flatten()`, which passes the result array by reference.
     * This achieves O(N) complexity and significantly reduces memory usage and execution time.
     */
    public static function flatten(NDArray $a): NDArray
    {
        $data = $a->getData();
        $flatData = [];
        \NumPHP\Utils\Helpers::flatten($data, $flatData);
        return new NDArray($flatData, $a->getDtype());
    }
}