## 2024-05-17 - Stop word filtering with associative arrays
**Learning:** Using `in_array` for stop word filtering in heavy NLP/RAG loops is a performance bottleneck in PHP. Searching an array is O(n), which is slow when called repeatedly for every token inside `preg_split` loops (like `LocalRAG::tokens`).
**Action:** Always convert static dictionaries or stop-word lists to hash maps (`['word' => true]`) and use `isset()` for O(1) lookups in tokenization and text processing routines.
## 2024-05-18 - Caching RAG queries
**Learning:** Found that `LocalRAG::loadAll()` does an expensive SQL query fetching 5000+ rows and decoding JSON from `tags_json` on every call. It is invoked on every single call to `answer()`.
**Action:** Adding a simple in-memory class property array as a cache prevents repeat database lookups during the same execution lifecycle, resulting in ~2x performance gains for subsequent calls.
