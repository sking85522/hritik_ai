<?php
namespace QuantizationPHP;

class QuantizationPHP {
    // Main entry point
}

class Quantizer {
    public static function quantizeInt8(array $weights): array {
        $flat = [];
        // Bolt Optimization: Replaced O(N^2) array_merge in loop with O(1) by-reference append
        self::flatten($weights, $flat);
        if (empty($flat)) return ['quantized' => $weights, 'scale' => 1.0, 'zero_point' => 0];

        $min = min($flat);
        $max = max($flat);

        $qMin = -128;
        $qMax = 127;

        $scale = ($max - $min) / ($qMax - $qMin);
        $scale = $scale == 0 ? 1 : $scale;

        $zeroPoint = round($qMin - $min / $scale);
        $zeroPoint = max($qMin, min($qMax, $zeroPoint));

        $quantized = self::mapRecursive($weights, function($w) use ($scale, $zeroPoint, $qMin, $qMax) {
            $q = round($w / $scale + $zeroPoint);
            return max($qMin, min($qMax, $q));
        });

        return [
            'quantized' => $quantized,
            'scale' => $scale,
            'zero_point' => $zeroPoint
        ];
    }

    public static function dequantizeInt8(array $quantizedData): array {
        $quantized = $quantizedData['quantized'];
        $scale = $quantizedData['scale'];
        $zeroPoint = $quantizedData['zero_point'];

        return self::mapRecursive($quantized, function($q) use ($scale, $zeroPoint) {
            return ($q - $zeroPoint) * $scale;
        });
    }

    private static function flatten(array $array): array {
        $result = [];
        self::flattenRef($array, $result);
        return $result;
    }

    private static function flattenRef(array $array, array &$result): void {
        foreach ($array as $value) {
            if (is_array($value)) {
                self::flattenRef($value, $result);
    private static function flatten(array $array, array &$out): void {
        foreach ($array as $value) {
            if (is_array($value)) {
                self::flatten($value, $out);
    // Bolt Optimization: Replace O(N^2) array_merge in recursion with O(1) by-reference append
    private static function flatten(array $array, array &$result = []): array {
    // Bolt Optimization: Replace O(N^2) array_merge in loop with O(1) pass-by-reference array append
    private static function flatten(array $array, array &$result): void {
        foreach ($array as $value) {
            if (is_array($value)) {
                self::flatten($value, $result);
            } else {
                $out[] = $value;
            }
        }
    }

    private static function mapRecursive(array $array, callable $callback): array {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::mapRecursive($value, $callback);
            } else {
                $result[$key] = $callback($value);
            }
        }
        return $result;
    }
}
