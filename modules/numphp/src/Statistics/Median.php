<?php

namespace NumPHP\Statistics;

use NumPHP\Core\NDArray;

class Median
{
    public static function median(NDArray $a): float
    {
        $data = $a->getData();
        $flattened = [];
        // Bolt Optimization: Replaced O(N^2) array_merge with O(1) pass-by-reference recursion
        // Bolt Optimization: Replaced O(N^2) array_merge in loop with O(1) by-reference append
        \NumPHP\Utils\Helpers::flatten($data, $flattened);
        \NumPHP\Utils\Helpers::flatten($data, $flattened);
        self::flattenData($data, $flattened);
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
}
}
}

    private static function flatten($data, array &$result = []): void
    // Bolt Optimization: Replace O(N^2) array_merge in recursion with O(1) by-reference append
    private static function flattenData($data, array &$result): void
    // Bolt Optimization: Replace O(N^2) array_merge in loop with O(1) pass-by-reference array append
    private static function flatten($data, array &$result): void
    {
        if (!is_array($data)) {
            $result[] = $data;
            return;
        }

        foreach ($data as $value) {
        foreach ($data as $value) {
            if (is_array($value)) {
                self::flattenData($value, $result);
            } else {
                $result[] = $value;
            }
            self::flatten($value, $result);
        }
    }
}
