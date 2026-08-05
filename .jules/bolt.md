## 2024-05-19 - Fast PHP Matrix Multiplication

**Learning:** Transposing a matrix before multiplication speeds up column lookups but allocating that memory in PHP is incredibly heavy. The i-k-j loop order gives the same cache benefits for sequential row access in the inner loop WITHOUT the heavy memory cost of transposing first.

**Action:** When optimizing tight nested array loops in PHP, prioritize loop reordering (like i-k-j for matrix multiplication) over allocating memory for transposed matrices.
