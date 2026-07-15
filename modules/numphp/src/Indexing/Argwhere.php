<?php

namespace NumPHP\Indexing;

use NumPHP\Core\NDArray;

class Argwhere
{
    /**
     * Find the indices of array elements that are non-zero, grouped by element.
     *
     * @param NDArray $a
     * @return NDArray
     */
    public static function argwhere(NDArray $a): NDArray
    {
        $data = $a->getData();
        $indices = [];
        self::recursiveFind($data, [], $indices);
        if (empty($indices)) {
            return new NDArray([], 'int');
        }
        return new NDArray($indices, 'int');
    }

    /**
     * Bolt Optimization: Avoid O(N^2) array_merge in recursion by using PHP's
     * fast array copy and append. Reduces memory allocation overhead.
     */
    private static function recursiveFind($data, $current_index, &$indices)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $next_index = $current_index;
                $next_index[] = $key;
                self::recursiveFind($value, $next_index, $indices);
            }
        } elseif ($data != 0) {
            $indices[] = $current_index;
        }
    }
}