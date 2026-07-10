<?php

namespace NumPHP\Statistics;

use NumPHP\Core\NDArray;

class Median
{
    public static function median(NDArray $a): float
    {
        $data = $a->getData();
        $flattened = [];
        self::flatten($data, $flattened);
        sort($flattened);
        $count = count($flattened);
        $middle = floor(($count - 1) / 2);
        if ($count % 2) {
            return $flattened[$middle];
        } else {
            return ($flattened[$middle] + $flattened[$middle + 1]) / 2;
        }
    }

    // Bolt Optimization: Replace O(N^2) array_merge in loop with O(1) pass-by-reference array append
    private static function flatten($data, array &$result): void
    {
        if (!is_array($data)) {
            $result[] = $data;
            return;
        }
        foreach ($data as $value) {
            self::flatten($value, $result);
        }
    }
}