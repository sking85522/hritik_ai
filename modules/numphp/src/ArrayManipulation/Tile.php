<?php

namespace NumPHP\ArrayManipulation;

use NumPHP\Core\NDArray;

class Tile
{
    /**
     * Construct an array by repeating A the number of times given by reps.
     *
     * @param NDArray $a The input array.
     * @param int $reps The number of repetitions of A.
     * @return NDArray
     */
    public static function tile(NDArray $a, int $reps): NDArray
    {
        $data = $a->getData();
        if (!is_array($data)) $data = [$data];

        // Bolt Optimization: Replaced O(N^2) array_merge with O(1) foreach append
        $result = [];
        // Bolt Optimization: Replaced O(N^2) array_merge in loop with O(1) foreach append
        for ($i = 0; $i < $reps; $i++) {
            foreach ($data as $val) {
                $result[] = $val;
            }
        }

        return new NDArray($result, $a->getDtype());
    }
}