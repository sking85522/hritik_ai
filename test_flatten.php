<?php
function flatten($array) {
    $result = [];
    foreach ($array as $value) {
        if (is_array($value)) {
            foreach (flatten($value) as $v) {
                $result[] = $v;
            }
        } else {
            $result[] = $value;
        }
    }
    return $result;
}
function flattenRef($array, &$result = []) {
    foreach ($array as $value) {
        if (is_array($value)) {
            flattenRef($value, $result);
        } else {
            $result[] = $value;
        }
    }
    return $result;
}
$a = [1, [2, [3, 4], 5], 6];
print_r(flatten($a));
print_r(flattenRef($a));
