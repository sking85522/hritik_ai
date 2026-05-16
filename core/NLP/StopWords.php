<?php
namespace Core\NLP;

/**
 * HRITIK AI - STOP WORDS FILTER
 * Contains common English and Hindi stop words to clean neural tokens.
 */
class StopWords {
    private array $words = [
        'is', 'am', 'are', 'was', 'were', 'the', 'a', 'an', 'and', 'but', 'if', 'or', 'of', 'at', 'by', 'for', 'with', 'to', 'from', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'can', 'will', 'just', 'should', 'now', 'it', 'its', 'they', 'them', 'their', 'we', 'us', 'our',
        'hai', 'tha', 'thi', 'the', 'ka', 'ke', 'ki', 'main', 'ko', 'me', 'hi', 'he', 'hu', 'ho', 'se', 'ya', 'neeche', 'upar', 'liye', 'ne', 'par', 'per', 'ek', 'do', 'teen', 'aur', 'toh', 'to', 'raha', 'rahe', 'rahi', 'kuch', 'bhi', 'ab', 'jab', 'tab', 'kaun', 'kis', 'jis', 'unka', 'inka', 'isne', 'usne'
    ];

    /**
     * Alias for remove() to maintain compatibility with NLPPipeline.
     */
    public function filter(array $tokens): array {
        return $this->remove($tokens);
    }

    /**
     * Removes stop words from a token array.
     */
    public function remove(array $tokens): array {
        return array_values(array_filter($tokens, function($token) {
            return !in_array(strtolower($token), $this->words);
        }));
    }
}
