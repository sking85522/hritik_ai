<?php

namespace NumPHP\Statistics;

use NumPHP\Core\NDArray;

class Median
{
    public static function median(NDArray $a): float
    {
        $data = $a->getData();
        $flattened = [];
        self::flattenData($data, $flattened);
        sort($flattened);
        $count = count($flattened);
        $middle = floor(($count - 1) / 2);
        if ($count % 2) {
            return $flattened[$middle];
        } else {
            return ($flattened[$middle] + $flattened[$middle + 1]) / 2;
        }
    }

    // Bolt Optimization: Replace O(N^2) array_merge in recursion with O(1) by-reference append
    private static function flattenData($data, array &$result): void
    {
        if (!is_array($data)) {
            $result[] = $data;
            return;
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                self::flattenData($value, $result);
            } else {
                $result[] = $value;
            }
        }
    }
}
