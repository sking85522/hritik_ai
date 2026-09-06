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

## 2024-06-12 - Recursive array flattening optimization without external dependencies
**Learning:** When trying to optimize array flattening operations in an isolated module (e.g., `numphp`), updating local recursive methods to use an internal pass-by-reference array (e.g., `private static function recursiveFlatten($data, array &$result = []): void`) instead of returning and merging arrays (`array_merge`) preserves module isolation while maintaining the O(N) performance boost. This is preferable to introducing cross-file or cross-module dependencies to global helper classes.
**Action:** Always prefer local pass-by-reference accumulation for array building within isolated classes or modules to avoid O(N^2) memory reallocation without breaking architecture/isolation.
## 2024-07-08 - O(N²) array_merge in Recursive Flattening
**Learning:** In PHP, using `$array = array_merge($array, recursiveFlatten(...))` inside a recursive method creates a massive O(N²) performance bottleneck. PHP reallocates memory and copies all existing elements of `$array` on every recursion level, leading to exponential execution time as the array depth and size grow.
**Action:** Replace `array_merge` with passing an array by reference (`&$result`) into the recursive function and appending elements directly (`$result[] = $element;`). This enables O(1) appending per element and completely solves the memory reallocation scaling issue, keeping execution time strictly linear.
## 2024-07-20 - O(1) Pass-by-Reference Array Flattening Optimization
**Learning:** Found multiple instances where array flattening was implemented using an `array_merge()` function call inside a `foreach` loop recursively. In PHP, this creates an $O(N^2)$ algorithmic complexity because the growing `$result` array is copied and reallocated on every iteration.
**Action:** Replace `$result = array_merge($result, recursive_call($data))` with a method signature that accepts a pass-by-reference array `$result` as an argument (`function flatten($data, array &$result = [])`). Append to it directly using `$result[] = $element`. This changes complexity to $O(N)$.

## 2024-07-05 - Foreach over chained array operations
**Learning:** In PHP, replacing a chain of `array_filter` followed by `array_map` with a single `foreach` loop provides significant performance boosts (e.g. ~25% speedup in token analysis). This is because it reduces array iterations from multiple passes to just one, and completely eliminates the overhead of closures (anonymous function calls).
**Action:** When identifying tight loops, especially in text tokenization and analysis, refactor mapped/filtered loops into native C-backed loop structures or a single standard `foreach` loop to save multi-pass iteration time and closure allocations.
## 2024-06-25 - Combined array_filter and array_map with foreach loop
**Learning:** In PHP, chaining `array_filter` and `array_map` with closures causes unnecessary function call and closure overhead. When both are used in sequence (e.g., in a text tokenizer that filters stopwords and stems tokens), iterating over the array twice with closures is noticeably slower than a single pass using a direct `foreach` loop.
**Action:** When filtering and subsequently mapping an array in a performance-critical path, combine the logic into a single `foreach` loop. This avoids the overhead of closures and multiple iterations, yielding measurable speedups (e.g., nearly 2x faster).
## 2024-06-10 - Custom recursive flattening vs array_walk_recursive
**Learning:** For recursively flattening multidimensional arrays in PHP, custom iterative or recursive functions using a pass-by-reference output array (e.g., `&$result`) execute significantly faster (about 3-4x) than the native `array_walk_recursive` function, because they avoid the overhead of the closure callback on every leaf node.
**Action:** When implementing `flatten` utility functions across the codebase, prefer a custom recursive function using `&$result` over `array_walk_recursive`.
## 2024-07-28 - Avoid array_merge in Array Path Recursion
**Learning:** Similar to recursive flattening, building index paths dynamically using `array_merge($current_index, [$key])` inside a recursive loop (such as in an `argwhere` function) introduces O(N²) scaling overhead due to array reallocation. It's much faster to perform a standard assignment array copy (`$next_index = $current_index`) and then append to the copy directly (`$next_index[] = $key`).
**Action:** When maintaining state paths in recursion (like tracking the multi-dimensional index path), never use `array_merge`. Instead copy the array path explicitly using `=` and append the new path component directly using `[] =`.

## 2026-07-14 - Array_merge vs Array appending for recursive tracking
**Learning:** Using `array_merge($current_index, [$key])` to pass updated array paths into recursive functions is unnecessarily slow in PHP because it creates a new array and copies all elements on every recursive step.
**Action:** Copy the array using assignment and append natively instead (e.g., `$next_index = $current_index; $next_index[] = $key;`). This provides roughly a 4x performance improvement by eliminating the `array_merge` function call and overhead.
## 2024-07-12 - PHP array_merge in Recursive Loops
**Learning:** In PHP, using `array_merge` inside deep recursive loops (like parsing multidimensional numerical arrays in `Argwhere`) causes heavy memory reallocation overhead (O(N) operation per step).
**Action:** Replace `array_merge` inside recursive structure traversal with O(1) stack operations: `$array[] = $val; recursiveCall($array); array_pop($array);`. This achieves massive speedups while maintaining the same immutable behavior down the stack.

## 2026-07-16 - O(N²) array_merge in calculateShape
**Learning:** In PHP, using `array_merge` recursively to build a shape array (like `calculateShape` in Buffer/NDArray) causes heavy memory reallocation overhead (O(N²) complexity).
**Action:** Replace recursive `array_merge` with an iterative `while` loop that directly appends to the array using `$shape[] = count($level)`. This shifts the complexity to O(N) and can provide upwards of 90x speedup for deeply nested structures.
