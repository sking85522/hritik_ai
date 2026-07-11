<?php
$data = [];
// Create a deeply nested array structure
$current = &$data;
for ($i = 0; $i < 1000; $i++) {
    $current[] = $i;
    $current[] = [];
    $current = &$current[count($current) - 1];
}

// flatten with array_merge
function flattenMerge($data) {
    if (!is_array($data)) return [$data];
    $result = [];
    foreach ($data as $value) {
        $result = array_merge($result, flattenMerge($value));
    }
    return $result;
}

// flatten with pass-by-reference
function flattenRef($data, &$result = []) {
    if (!is_array($data)) {
        $result[] = $data;
        return $result;
    }
    foreach ($data as $value) {
        if (is_array($value)) {
            flattenRef($value, $result);
        } else {
            $result[] = $value;
        }
    }
    return $result;
}

$start = microtime(true);
flattenMerge($data);
$timeMerge = microtime(true) - $start;

$start = microtime(true);
$res = [];
flattenRef($data, $res);
$timeRef = microtime(true) - $start;

echo "Merge: {$timeMerge}s\n";
echo "Ref: {$timeRef}s\n";
