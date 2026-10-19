## 2024-11-20 - O(N^2) Overheads in Pandas PHP
**Learning:** Method calls inside loops in PHP add considerable overhead, especially in data processing systems like DataFrames (e.g., PandaPHP). Array-fetching operations such as `$df->get()` should be pre-fetched into a standard PHP array to achieve O(1) direct access.
Additionally, `array_merge()` inside nested loops for joins causes massive memory reallocation and O(N^2) complexity.
**Action:** Always pre-fetch arrays before loops. Replace `array_merge` with the PHP 8.1+ spread operator (`[...$a, ...$b]`) inside intensive loops.
## 2024-05-19 - Fast PHP Matrix Multiplication
## 2024-06-25 - Avoid O(N^2) array_merge in shape calculation
**Learning:** In PHP, using `array_merge` inside a recursive function to build an array (like the shape of an N-dimensional array) results in constant reallocation and O(N^2) complexity. This causes significant performance and memory overhead for deeply nested structures.
**Action:** Replace recursive `array_merge` patterns with simple, stateful O(N) iterative loops when processing deep structures to minimize memory allocations and improve speed.
## 2024-03-24 - Pre-compiling Regex vs in_array Optimization
**Learning:** In PHP, the PCRE engine internally caches compiled regular expressions. Grouping multiple `preg_match` calls into a single massive regex does not provide a noticeable speedup over sequential `preg_match` calls, and modifying regex patterns (like adding word boundaries) risks changing application logic.
**Action:** When looking for fast, safe array lookups inside loops, replace `in_array` or `array_intersect` with a static associative map and use `isset()`. This allows O(1) lookups and early returns without altering core matching logic.

**Learning:** Transposing a matrix before multiplication speeds up column lookups but allocating that memory in PHP is incredibly heavy. The i-k-j loop order gives the same cache benefits for sequential row access in the inner loop WITHOUT the heavy memory cost of transposing first.

**Action:** When optimizing tight nested array loops in PHP, prioritize loop reordering (like i-k-j for matrix multiplication) over allocating memory for transposed matrices.
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
## 2024-08-04 - Array Spread vs array_merge
**Learning:** In PHP, using the array spread operator `[...$array1, ...$array2]` is generally faster than `array_merge($array1, $array2)` because it is handled natively by the Zend Engine parser as a language construct rather than a function call, reducing overhead.
**Action:** Replace `array_merge` with the spread operator `[...$a, ...$b]` where practical, especially in tight loops.
## 2024-08-01 - O(N²) array_merge in Recursive Shape Calculation
**Learning:** Found an instance in `Buffer.php` where calculating the shape of an N-dimensional array was done recursively, merging the result of the child shape with `array_merge`. This causes an O(N²) algorithmic complexity with recursive overhead, severely slowing down large NDArray creations.
**Action:** Replace the recursive `array_merge` pattern for multidimensional shape extraction with a simple iterative `while (is_array($level))` loop. This reduces the complexity to O(N) and completely eliminates both `array_merge` allocations and function call overhead, yielding up to a 47x speedup for deeply nested structures.

## 2024-07-29 - PHP Matrix Operations Optimization
**Learning:** In PHP, using `array_map(null, ...$matrix)` is significantly faster (~5x) for transposing 2D arrays than using nested loops, as it avoids PHP-level iterations and leverages internal C functions. Also, caching `count($array)` in loops and using row references (`$aRow = $a[$i]`) provides measurable (~2x) speedups in matrix multiplication. Warning: The array spread operator `...$matrix` can throw an `ArgumentCountError` on exceptionally massive datasets, so consider limits before utilizing this approach on unconstrained input.
**Action:** Use `array_map(null, ...$matrix)` for array transposition where the number of rows is within reasonable memory limits. Cache array lengths and avoid re-indexing 2D arrays inside innermost loops where possible for `matmul`.
## 2024-07-28 - array_merge vs foreach loop overhead
**Learning:** In PHP, using `array_merge` to combine large arrays (e.g., accumulating chunks of byte segments during decoding) causes an O(N^2) memory reallocation overhead on every call. This dramatically degrades performance for large inputs, resulting in massive scaling delays (e.g. going from 0.03 seconds to ~75 seconds for just 10000 append operations). Replacing `array_merge($array, $readBytes)` with a simple `foreach($readBytes as $b) { $array[] = $b; }` prevents these huge copying overheads by appending elements sequentially in O(1) per element.
**Action:** When iteratively appending arrays, use a `foreach` loop to append elements sequentially instead of `array_merge` to avoid severe performance degradation.
## 2024-07-30 - O(N²) array_merge in Shape Calculation
**Learning:** In PHP, calculating the shape of multidimensional arrays using `array_merge` recursively (e.g., `$shape = array_merge($shape, calculateShape($data[0]))`) has O(N²) algorithmic complexity and severe memory reallocation overhead as array depth grows.
**Action:** Always prefer iterative while loops (`while (is_array($level))`) to traverse deeply nested array dimensions for calculating shape. It scales strictly linearly, uses zero extra memory allocation, and is significantly faster than both recursive `array_merge` and recursive pass-by-reference.
## 2024-07-28 - strtr vs str_replace for Dictionary Mappings
**Learning:** In PHP, using `strtr($text, $mapping)` for dictionary-based string replacements is significantly faster (~2.2x speedup) than iterating over the mapping array with a `foreach` loop and calling `str_replace()`. It is also faster than calling `str_replace(array_keys($mapping), array_values($mapping), $text)`. This is because `strtr()` operates natively in C and performs a single pass over the string for replacements without additional array overhead.
**Action:** When performing dictionary-based string normalizations or translations, always use `strtr($text, $mapping)` instead of iterating with `str_replace`.
## 2024-07-26 - O(N²) array_merge in Array Shape Calculation
**Learning:** In PHP, computing the multi-dimensional shape of an array recursively using `array_merge` creates an O(N²) bottleneck due to constant reallocation and copying of elements for each depth level. This is particularly problematic for highly nested structures like neural network weights.
**Action:** Replaced recursive `array_merge` in `Buffer::calculateShape` with an iterative `while` loop that explicitly traverses to `$data[0]` and dynamically appends dimensions using `$shape[] = count($data);`. This achieves O(1) appending per level, improving deep shape calculation speed drastically.
## 2024-07-31 - Array_merge in Recursion for Shape Calculation
**Learning:** Found an instance in `Buffer::calculateShape` where `array_merge` was used inside a recursive method to calculate the multi-dimensional shape of an array. Even for small depths, recursive function call overhead combined with `array_merge` allocation slows down fundamental operations.
**Action:** Replace the recursive `array_merge` pattern with an iterative `while` loop that directly appends to the array (e.g., `$shape[] = count($level)`). This transforms the operation from an O(N²) recursive scaling overhead to a highly efficient O(N) iterative structure, yielding an ~8x performance improvement in benchmarks.

## 2024-08-01 - O(N²) array_merge in Array Shape Recursion
**Learning:** Similar to recursive flattening, calculating the shape of multidimensional tensors using `array_merge` to combine the size of the current dimension with the shape of deeper dimensions recursively introduces O(N²) time complexity and creates unnecessary array copies. An iterative approach eliminates function call overhead entirely.
**Action:** Replace recursive `array_merge` calls for calculating array shape depths with a simple iterative `while` loop that walks down the first elements of the array (`$data = $data[0]`) and appends counts (`$shape[] = count($data)`). This reduces complexity to O(N) and makes it significantly faster for highly nested arrays/tensors.
## 2025-01-28 - O(N²) array_merge in Array Shape Calculation
**Learning:** Found an anti-pattern in `NumPHP/Core/Buffer.php` where calculating the shape of multidimensional arrays used a recursive function calling `array_merge($shape, calculateShape($data[0]))`. As established in previous learnings, `array_merge` creates a new array and copies all elements at every depth level, leading to O(N²) overhead.
**Action:** Replace the recursive `array_merge` implementation with an iterative `while(is_array($level))` loop that counts elements at each depth, exactly like how it is optimized in `NDArray.php`. This provides an O(1) space and O(N) time calculation for multidimensional array shapes.
## 2024-07-29 - O(N) Array Iteration Overhead with array_merge
**Learning:** Using `array_merge($array1, $array2)` solely to iterate over the combined elements via a `foreach` loop (e.g., `foreach (array_merge($tokens, $attentionTokens) as $token)`) introduces unnecessary O(N) memory allocation and array copying overhead. In hot loops, this is a significant bottleneck.
**Action:** Replace `foreach (array_merge(...))` with consecutive `foreach` loops for each array when the goal is just iteration. This keeps operations O(1) in memory allocation and avoids full array duplication, yielding substantial speedups.
## 2024-08-05 - Avoid array_merge for calculating NDArray shapes
**Learning:** Using `array_merge` recursively to calculate the shape of multi-dimensional arrays (like deeply nested lists of lists) causes O(N²) time complexity and high overhead in PHP. Replacing the recursion with an iterative `while` loop that walks down the nested levels is significantly faster (O(N)) and avoids call stack limits.
**Action:** Replace recursive `array_merge` shape calculations with a fast O(N) iterative `while` loop when computing NDArray shapes.

## 2026-07-16 - O(N²) array_merge in calculateShape
**Learning:** In PHP, using `array_merge` recursively to build a shape array (like `calculateShape` in Buffer/NDArray) causes heavy memory reallocation overhead (O(N²) complexity).
**Action:** Replace recursive `array_merge` with an iterative `while` loop that directly appends to the array using `$shape[] = count($level)`. This shifts the complexity to O(N) and can provide upwards of 90x speedup for deeply nested structures.
