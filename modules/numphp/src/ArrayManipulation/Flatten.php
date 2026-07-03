<?php

namespace NumPHP\ArrayManipulation;

use NumPHP\Core\NDArray;

class Flatten
{
    public static function flatten(NDArray $a): NDArray
    {
        $data = $a->getData();
        $flatData = self::recursiveFlatten($data);
        return new NDArray($flatData, $a->getDtype());
    }

    // Bolt Optimization: Replaced O(N^2) array_merge with O(1) pass-by-reference array
    private static function recursiveFlatten($data, array &$result = []): array
    {
        if (!is_array($data)) {
            $result[] = $data;
            return $result;
        }

        foreach ($data as $element) {
            if (is_array($element)) {
                self::recursiveFlatten($element, $result);
            } else {
                $result[] = $element;
            }
        }

        return $result;
    }
}