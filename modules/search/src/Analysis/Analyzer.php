<?php

namespace SearchPHP\Analysis;

class Analyzer
{
    // Hash map for O(1) lookups instead of in_array
    private $stopWords = [
        'a' => true, 'about' => true, 'an' => true, 'and' => true, 'are' => true, 'as' => true,
        'at' => true, 'be' => true, 'by' => true, 'for' => true, 'from' => true, 'how' => true,
        'i' => true, 'in' => true, 'is' => true, 'it' => true, 'of' => true, 'on' => true,
        'or' => true, 'that' => true, 'the' => true, 'this' => true, 'to' => true, 'was' => true,
        'what' => true, 'when' => true, 'where' => true, 'who' => true, 'will' => true, 'with' => true
    ];

    public function analyze(string $text): array
    {
        // Lowercase
        $text = strtolower($text);

        // Remove punctuation and special characters, keep only alphanumerics and spaces
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        // Tokenize by splitting on whitespace
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filter out stop words and short tokens, and apply simple stemming in a single pass
        // Optimization: Replacing array_filter and array_map closures with a direct foreach loop
        // avoids function call overhead, yielding a significant speedup for text tokenization.
        $result = [];
        foreach ($tokens as $token) {
            if (strlen($token) > 1 && !isset($this->stopWords[$token])) {
                // Stemming could be added here (e.g., Porter Stemmer), but we will keep it simple for now
                // Or simple suffix stripping (very basic)
                if (substr($token, -3) === 'ing') {
                    $result[] = substr($token, 0, -3);
                } elseif (substr($token, -2) === 'es' && strlen($token) > 4) {
                    $result[] = substr($token, 0, -2);
                } elseif (substr($token, -1) === 's' && strlen($token) > 3 && substr($token, -2) !== 'ss') {
                    $result[] = substr($token, 0, -1);
                } else {
                    $result[] = $token;
                }
            }
        }

        return $result;
    }
}
