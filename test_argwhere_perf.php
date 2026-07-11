<?php
$data = [];
$current = &$data;
for ($i = 0; $i < 1000; $i++) {
    $current[] = $i;
    $current[] = [];
    $current = &$current[count($current) - 1];
}

function recursiveFindMerge($data, $current_index, &$indices) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            recursiveFindMerge($value, array_merge($current_index, [$key]), $indices);
        }
    } elseif ($data != 0) {
        $indices[] = $current_index;
    }
}

function recursiveFindRef($data, $current_index, &$indices) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $next_index = $current_index;
            $next_index[] = $key;
            recursiveFindRef($value, $next_index, $indices);
        }
    } elseif ($data != 0) {
        $indices[] = $current_index;
    }
}

$start = microtime(true);
$indices = [];
recursiveFindMerge($data, [], $indices);
$timeMerge = microtime(true) - $start;

$start = microtime(true);
$indices = [];
recursiveFindRef($data, [], $indices);
$timeRef = microtime(true) - $start;

echo "Merge: {$timeMerge}s\n";
echo "Ref: {$timeRef}s\n";
