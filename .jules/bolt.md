## 2024-07-23 - Early Return and array_merge

**Learning:** PHP `array_merge` allocates a new array immediately. If `array_merge` is used in a `foreach` loop that has an early `return` condition, the full cost of merging both arrays is paid upfront, regardless of whether the entire combined array is actually iterated over.
**Action:** In methods that search through multiple arrays for a single match (early return), iterate through the arrays sequentially rather than merging them. This allows the search to terminate early without paying the allocation and processing cost for the later arrays.
