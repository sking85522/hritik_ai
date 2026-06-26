## 2024-03-24 - Pre-compiling Regex vs in_array Optimization
**Learning:** In PHP, the PCRE engine internally caches compiled regular expressions. Grouping multiple `preg_match` calls into a single massive regex does not provide a noticeable speedup over sequential `preg_match` calls, and modifying regex patterns (like adding word boundaries) risks changing application logic.
**Action:** When looking for fast, safe array lookups inside loops, replace `in_array` or `array_intersect` with a static associative map and use `isset()`. This allows O(1) lookups and early returns without altering core matching logic.

## 2024-05-25 - Combined Regular Expressions for O(1) Speedup
**Learning:** Found an anti-pattern specific to core NLP/Security logic where security validation and prompt sanitization heavily relied on iterating through arrays of individual regex strings in a `foreach` loop executing `preg_match` iteratively. Given PHP's architecture, running multiple `preg_match` checks sequentially in loops introduces huge overhead relative to a single, combined alternation regex (e.g., `/pattern1|pattern2/`) executed natively in C.
**Action:** When finding multiple sequential string or regex patterns being matched against a single string, always combine them into a single compiled regex string and make a single `preg_match` call for significant (up to 7-10x) micro-optimization speedups.

## 2024-05-25 - Regex Priorities vs Combinations in PHP
**Learning:** While combining multiple sequential `preg_match` string searches into a single `preg_match_all` query with named capturing groups and `PREG_SET_ORDER` can be fast, it fails to evaluate matches by strict code priority, instead matching whatever appears earliest in the target string. Using an array `foreach` loop over individual patterns is actually slower in PHP than explicitly writing sequential `if(preg_match())` checks.
**Action:** When a fallback parser requires strict precedence rules, sequential `if(preg_match)` checks natively short-circuit in C and are faster than looping. To optimize them without breaking priority, remove unnecessary outer capturing groups `/(...)/` and eliminate the `/i` case-insensitivity flag when the input is guaranteed to be transformed by `strtolower()`.

## 2024-05-29 - O(1) PHP Regex Group Matching
**Learning:** Found an anti-pattern in NLP Entity Extractor where regex matching inside a `foreach` loop iteratively maps matched elements to target arrays. Instead of nested loops doing individual regex, a combined alternation regex using `PREG_SET_ORDER` and named capture groups allows O(1) pattern matching directly in the C engine, drastically speeding up NLP extraction (2.4x - 4x speedup).
**Action:** For mapping dictionaries to entities, use combined regex with named capture groups `(?<name>pattern)` and extract the match keys rather than looping in PHP.

## 2024-06-03 - Avoid empty() for regex capture groups
**Learning:** Found an edge-case bug when optimizing sequential regex extraction logic with named or numbered capture groups. Checking if a capture group matched using `!empty($match[1])` is flawed because if the extracted value is literally `"0"` (e.g., a variable named zero), `empty('0')` evaluates to `true` in PHP. This causes the code to falsely assume the group didn't match and fall back to the wrong array index, throwing an `Undefined array key` warning.
**Action:** When determining which regex alternation branch matched in a capture group array, avoid `empty()`. Instead, use the null coalescing operator `??` (e.g., `$vars[] = $match[2] ?? $match[1];`) or explicitly check against an empty string `$match[1] !== ''`.

## 2024-06-03 - Combining Regex Alternations Safely
**Learning:** When refactoring sequential regex conditionals (e.g. `if (preg_match(...))`) into a single combined regex using alternation (`|`) and named groups, using `preg_match` is still extremely fast, but iterating over capture groups to determine which one matched using an `isset` check can be optimized.
**Action:** When combining patterns, use `preg_match($pattern, $text, $matches)` and verify named capture groups. Ensure you check for empty string matches (`$matches['group'] !== ''`) to safely determine which branch fired without sacrificing precedence logic if ordered correctly.
## 2024-06-05 - str_ireplace array optimization
**Learning:** In PHP, passing an array of strings directly to `str_replace()` or `str_ireplace()` is significantly faster than iterating over the array with a userland `foreach` loop, as the iteration is handled natively in the highly optimized C engine. I also learned to be careful about not inadvertently committing local run artifacts like `storage/local_db.json`.
**Action:** When performing string replacements across an array of search terms or dictionary mappings, pass the arrays directly to the string replacement function instead of looping in PHP.

## 2024-06-10 - O(N²) array_merge in PHP Loops
**Learning:** In PHP, using `$array = array_merge($array, $new_elements)` inside a loop (especially for numerical operations or neural network recursive parsing) causes a massive O(N²) performance bottleneck. PHP reallocates memory and copies all existing elements of `$array` on every iteration, leading to exponential execution time as the array grows.
**Action:** Replace `$array = array_merge($array, $new_elements)` inside loops with `foreach ($new_elements as $item) { $array[] = $item; }`. This enables O(1) appending per element and completely solves the memory reallocation scaling issue, keeping execution time strictly linear. Note: While array spread operators (`array_push($array, ...$new_elements)`) are slightly faster, they risk throwing an `ArgumentCountError` on massive numerical datasets, so the `foreach` strategy is safer.
## 2024-06-08 - array_filter vs foreach optimization
**Learning:** In PHP, replacing `array_filter` mapped with a closure by a direct `foreach` loop eliminates function call overhead, yielding significant performance gains (~2x to 3x) in computationally heavy paths like text tokenization. Also, replacing `preg_split` followed by `array_filter` with `preg_split(..., -1, PREG_SPLIT_NO_EMPTY)` is much faster (~2x to 3x) because the filtering is done natively in C instead of iterating the array in PHP.
**Action:** When filtering array results from `preg_split`, always use the `PREG_SPLIT_NO_EMPTY` flag instead of a separate `array_filter` call. When filtering arrays with custom logic (like `strlen > 1`), prefer a direct `foreach` loop over `array_filter` with a closure for hot loops.

## 2024-06-12 - Pass-by-Reference Flatten Optimization
**Learning:** When creating multi-dimensional array flattening utility functions (`Flatten::flatten()`, `Median::median()`, `Quantizer::flatten()`), a recursive approach using `array_merge()` creates an exponential O(N²) penalty due to memory reallocation in PHP loops.
**Action:** Always implement recursive multi-dimensional array flattening by using a pass-by-reference output array `&$result`. This turns an O(N²) array merge bottleneck into an O(1) array append operation (`$result[] = $val;`), drastically improving array generation speed in `NumPHP` and `QuantizationPHP` modules without altering the output structure.
