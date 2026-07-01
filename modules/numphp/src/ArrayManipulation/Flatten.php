<?php

namespace NumPHP\ArrayManipulation;

use NumPHP\Core\NDArray;

class Flatten
{
    public static function flatten(NDArray $a): NDArray
    {
        $data = $a->getData();
        $flatData = [];
        self::recursiveFlatten($data, $flatData);
        return new NDArray($flatData, $a->getDtype());
    }

    private static function recursiveFlatten($data, array &$result = []): void
    {
        if (!is_array($data)) {
            $result[] = $data;
            return;
        }

        foreach ($data as $element) {
            if (is_array($element)) {
                self::recursiveFlatten($element, $result);
            } else {
                $result[] = $element;
            }
        }
    }
}