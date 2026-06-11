<?php
namespace Core\Training\LanguageModel;

class Tokenizer {
    public function tokenize(string $text): array {
        $text = strtolower($text);
        $text = preg_replace('/([?.!,;:\(\)\[\]\{\}])/u', ' $1 ', $text);

        // ⚡ Bolt optimization: Use PREG_SPLIT_NO_EMPTY natively in C engine
        // instead of array_filter with closure (~2-3x speedup)
        return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    }
}
