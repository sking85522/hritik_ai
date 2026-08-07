## 2024-06-25 - Avoid O(N^2) array_merge in shape calculation
**Learning:** In PHP, using `array_merge` inside a recursive function to build an array (like the shape of an N-dimensional array) results in constant reallocation and O(N^2) complexity. This causes significant performance and memory overhead for deeply nested structures.
**Action:** Replace recursive `array_merge` patterns with simple, stateful O(N) iterative loops when processing deep structures to minimize memory allocations and improve speed.
