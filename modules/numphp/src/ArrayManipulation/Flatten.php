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

    private static function recursiveFlatten($data, array &$result = []): array
    {
        if (!is_array($data)) {
            $result[] = $data;
            return $result;
        }

        foreach ($data as $element) {
            if (is_array($element)) {
                // Bolt Optimization: Pass array by reference to avoid O(N^2) array_merge overhead
                self::recursiveFlatten($element, $result);
            } else {
                $result[] = $element;
            }
        }

        return $result;
    }
}