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
}