<?php

namespace NumPHP\Statistics;

use NumPHP\Core\NDArray;

class Median
{
    public static function median(NDArray $a): float
    {
        $data = $a->getData();
        $flattened = self::flatten($data);
        sort($flattened);
        $count = count($flattened);
        $middle = floor(($count - 1) / 2);
        if ($count % 2) {
            return $flattened[$middle];
        } else {
            return ($flattened[$middle] + $flattened[$middle + 1]) / 2;
        }
    }

    // Bolt Optimization: Replaced O(N^2) array_merge with O(1) pass-by-reference array
    private static function flatten($data, array &$result = [])
    {
        if (!is_array($data)) {
            $result[] = $data;
            return $result;
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                self::flatten($value, $result);
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }
}