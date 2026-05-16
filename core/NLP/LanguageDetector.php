<?php
namespace Core\NLP;

/**
 * HRITIK AI - MULTILINGUAL LANGUAGE DETECTOR
 * Support for Hindi (Devanagari), English, Arabic, and Hinglish.
 */
class LanguageDetector {
    
    private array $hiMarkers = [
        'hai', 'tha', 'thi', 'the', 'ka', 'ki', 'main', 'nahi', 'nhi', 'kyun', 'kya', 
        'karo', 'kar', 'raha', 'rahi', 'kuch', 'bhi', 'se', 'ya', 'par', 'per', 'kaise', 'btao'
    ];

    /**
     * Detect language using Unicode Scripts and common markers.
     */
    public function detect(string $text): string {
        $text = trim($text);
        if (empty($text)) return 'en';

        // 1. Script Analysis (Devanagari)
        if (preg_match('/\p{Devanagari}/u', $text)) {
            return 'hi_native';
        }

        // 2. Script Analysis (Arabic/Urdu)
        if (preg_match('/\p{Arabic}/u', $text)) {
            return 'ur';
        }

        // 3. Keyword Analysis (Hinglish/Latin Hindi)
        $words = explode(' ', strtolower($text));
        $hiCount = 0;
        foreach ($words as $word) {
            if (in_array($word, $this->hiMarkers)) $hiCount++;
        }

        if ($hiCount >= 1) return 'hi_latin';

        // Default to English
        return 'en';
    }
}
