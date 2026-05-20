## 2024-05-17 - Stop word filtering with associative arrays
**Learning:** Using `in_array` for stop word filtering in heavy NLP/RAG loops is a performance bottleneck in PHP. Searching an array is O(n), which is slow when called repeatedly for every token inside `preg_split` loops (like `LocalRAG::tokens`).
**Action:** Always convert static dictionaries or stop-word lists to hash maps (`['word' => true]`) and use `isset()` for O(1) lookups in tokenization and text processing routines.
## 2026-05-20 - Stop word filtering with associative arrays
**Learning:** Using `in_array` for stop word and tech term filtering in heavy NLP loops is a performance bottleneck in PHP. Searching an array is O(n), which is slow when called repeatedly.
**Action:** Replaced static dictionaries and stop-word lists with hash maps (`['word' => true]`) and `isset()` for O(1) lookups in tokenization and text processing routines across core files.
