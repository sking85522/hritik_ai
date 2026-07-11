<?php
$a = [1, 2, [3, 0], 0];
$indices = [];
function recursiveFind($data, $current_index, &$indices) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $next_index = $current_index;
            $next_index[] = $key;
            recursiveFind($value, $next_index, $indices);
        }
    } elseif ($data != 0) {
        $indices[] = $current_index;
    }
}
recursiveFind($a, [], $indices);
print_r($indices);
