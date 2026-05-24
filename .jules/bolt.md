## 2024-05-17 - Stop word filtering with associative arrays
**Learning:** Using `in_array` for stop word filtering in heavy NLP/RAG loops is a performance bottleneck in PHP. Searching an array is O(n), which is slow when called repeatedly for every token inside `preg_split` loops (like `LocalRAG::tokens`).
**Action:** Always convert static dictionaries or stop-word lists to hash maps (`['word' => true]`) and use `isset()` for O(1) lookups in tokenization and text processing routines.
## 2024-05-18 - Caching RAG queries
**Learning:** Found that `LocalRAG::loadAll()` does an expensive SQL query fetching 5000+ rows and decoding JSON from `tags_json` on every call. It is invoked on every single call to `answer()`.
**Action:** Adding a simple in-memory class property array as a cache prevents repeat database lookups during the same execution lifecycle, resulting in ~2x performance gains for subsequent calls.
## 2024-05-19 - Regex Compilation in Loops
**Learning:** Running `preg_match` or `preg_replace` inside a `foreach` loop for dozens or hundreds of patterns (like in intent mappers or auto-spellers) causes significant performance bottlenecks. It compiles each regex individually per execution.
**Action:** Always pre-compile regex patterns. For `preg_match` (IntentMapper), combine multiple patterns into a single alternation regex string (e.g. `/\b(pattern1|pattern2)\b/i`). For `preg_replace` (AutoSpeller), prepare two arrays of patterns and replacements to utilize PHP C engine's native array replacement capability.
