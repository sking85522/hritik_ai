<?php

namespace NumPHP\ArrayManipulation;

use NumPHP\Core\NDArray;

class Flatten
{
    public static function flatten(NDArray $a): NDArray
    {
        $data = $a->getData();
        $flatData = [];
        \NumPHP\Utils\Helpers::flatten($data, $flatData);
        return new NDArray($flatData, $a->getDtype());
    }
        return new NDArray($flatData, $a->getDtype());
    }
        self::recursiveFlatten($data, $flatData);
        return new NDArray($flatData, $a->getDtype());
    }

    // Bolt Optimization: Replace O(N^2) array_merge in recursion with O(1) by-reference append
    // Bolt Optimization: Replace O(N^2) array_merge in loop with O(1) pass-by-reference array append
    /**
     * Bolt Optimization: Replaced O(N^2) array_merge with O(1) appends by passing result array by reference.
     */
    private static function recursiveFlatten($data, array &$result): void
    {
        if (!is_array($data)) {
            $result[] = $data;
            return;
        }

        foreach ($data as $element) {
            if (is_array($element)) {
                // Bolt Optimization: Replaced O(N^2) array_merge with O(1) recursive append by reference
                self::recursiveFlatten($element, $result);
            } else {
                $result[] = $element;
            }
        }
    }
}