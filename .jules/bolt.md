## 2024-07-12 - PHP array_merge in Recursive Loops
**Learning:** In PHP, using `array_merge` inside deep recursive loops (like parsing multidimensional numerical arrays in `Argwhere`) causes heavy memory reallocation overhead (O(N) operation per step).
**Action:** Replace `array_merge` inside recursive structure traversal with O(1) stack operations: `$array[] = $val; recursiveCall($array); array_pop($array);`. This achieves massive speedups while maintaining the same immutable behavior down the stack.
