<?php
$data = [];
$current = &$data;
for ($i = 0; $i < 1000; $i++) {
    $current[0] = [];
    $current = &$current[0];
}

function calculateShapeMerge($data) {
    $shape = [];
    if (is_array($data)) {
        $shape[] = count($data);
        if (isset($data[0])) {
            $shape = array_merge($shape, calculateShapeMerge($data[0]));
        }
    }
    return $shape;
}

function calculateShapeSpread($data) {
    $shape = [];
    if (is_array($data)) {
        $shape[] = count($data);
        if (isset($data[0])) {
            $shape = [...$shape, ...calculateShapeSpread($data[0])];
        }
    }
    return $shape;
}

function calculateShapeRef($data, &$shape = []) {
    if (is_array($data)) {
        $shape[] = count($data);
        if (isset($data[0])) {
            calculateShapeRef($data[0], $shape);
        }
    }
    return $shape;
}

$start = microtime(true);
calculateShapeMerge($data);
$timeMerge = microtime(true) - $start;

$start = microtime(true);
calculateShapeSpread($data);
$timeSpread = microtime(true) - $start;

$start = microtime(true);
$shape = [];
calculateShapeRef($data, $shape);
$timeRef = microtime(true) - $start;

echo "Merge: {$timeMerge}s\n";
echo "Spread: {$timeSpread}s\n";
echo "Ref: {$timeRef}s\n";
