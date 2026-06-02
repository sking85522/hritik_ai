<?php
namespace Core\Lang;

/**
 * HRITIK AI - ADVANCED NEURAL TRANSLATOR
 * Dynamically translates and smoothens responses using neural dictionary mapping.
 */
class NeuralTranslator {
    
    private array $mappings = [
        'theek' => 'okay',
        'shi' => 'correct',
        'karo' => 'do',
        'batao' => 'tell',
        'kaise' => 'how'
    ];

    /**
     * Translates and normalizes text for better engine understanding.
     */
    public function translate(string $text): string {
        $words = explode(' ', strtolower($text));
        $translated = [];
        foreach ($words as $word) {
            $translated[] = $this->mappings[$word] ?? $word;
        }
        return implode(' ', $translated);
    }

    /**
     * Aggressively cleans the response to make it human-like.
     * Removes robotic artifacts, thinking blocks, and system noise.
     */
    public function smoothen(string $text): string {
        // 1. Remove [Thinking...] or [NEURAL_LOGIC] blocks
        $text = preg_replace('/\[.*?\]/s', '', $text);
        
        // 2. Remove common JSON/Data artifacts
        $text = str_replace(['{', '}', '"', 'q:', 'a:', 'fact:', 'result:'], '', $text);
        
        // 3. Remove excess whitespace and newlines
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Detects the dominant language (en, hi, or hinglish).
     */
    public function detectLanguage(string $text): string {
        // Optimization: Static associative array for O(1) lookups instead of array_intersect
        static $hiMarkers = [
            'hai' => true, 'hain' => true, 'tha' => true, 'the' => true,
            'kya' => true, 'kaise' => true, 'aur' => true, 'toh' => true
        ];

        $words = explode(' ', strtolower($text));
        $hiCount = 0;

        foreach ($words as $word) {
            if (isset($hiMarkers[$word])) {
                $hiCount++;
                // Early return optimization
                if ($hiCount > 2) return 'hi';
            }
        }
        
        if ($hiCount > 0) return 'hinglish';
        return 'en';
    }
}
