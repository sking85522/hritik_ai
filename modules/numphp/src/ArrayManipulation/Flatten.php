<?php

namespace NumPHP\ArrayManipulation;

use NumPHP\Core\NDArray;

class Flatten
{
    public static function flatten(NDArray $a): NDArray
    {
        $data = $a->getData();
        $flatData = [];
        // Bolt Optimization: Replaced O(N^2) array_merge in loop with O(1) by-reference append
        \NumPHP\Utils\Helpers::flatten($data, $flatData);
        return new NDArray($flatData, $a->getDtype());
    }
}