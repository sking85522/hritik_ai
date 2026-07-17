## 2024-11-20 - O(N^2) Overheads in Pandas PHP
**Learning:** Method calls inside loops in PHP add considerable overhead, especially in data processing systems like DataFrames (e.g., PandaPHP). Array-fetching operations such as `$df->get()` should be pre-fetched into a standard PHP array to achieve O(1) direct access.
Additionally, `array_merge()` inside nested loops for joins causes massive memory reallocation and O(N^2) complexity.
**Action:** Always pre-fetch arrays before loops. Replace `array_merge` with the PHP 8.1+ spread operator (`[...$a, ...$b]`) inside intensive loops.
