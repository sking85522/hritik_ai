<?php
namespace Core\NLP;

/**
 * HRITIK AI - ADVANCED HINGLISH PROCESSOR
 * Handles the nuances of bilingual (Hindi-English) conversational flows.
 */
class HinglishProcessor {
    
    private array $normalizationMap = [
        'shi' => 'sahi',
        'theek' => 'thik',
        'kro' => 'karo',
        'krna' => 'karna',
        'btao' => 'batao',
        'dikhao' => 'dikhaye',
        'kese' => 'kaise',
        'vha' => 'wahan',
        'jha' => 'jahan',
        'moka' => 'mauka',
        'jrurat' => 'zaroorat'
    ];

    /**
     * Normalizes Hinglish words to a standard form for better matching.
     */
    public function normalize(string $text): string {
        $words = explode(' ', strtolower($text));
        $normalized = [];
        foreach ($words as $word) {
            $normalized[] = $this->normalizationMap[$word] ?? $word;
        }
        
        $text = implode(' ', $normalized);
        
        // Advanced Suffix Stripping for Hindi Verbs in Hinglish
        // Strips common endings like -o, -iye, -na, -ke, -raha
        return preg_replace('/(iye|oge|ega|ke|raha|rha|na)$/u', '', $text);
    }

    /**
     * Detects if the sentence is primarily Hinglish.
     */
    public function detectHinglish(string $text): bool {
        static $hinglishMarkers = [
            'hai' => true, 'hain' => true, 'tha' => true, 'thi' => true, 'the' => true,
            'kya' => true, 'kaise' => true, 'kyu' => true, 'aur' => true, 'toh' => true,
            'sahi' => true, 'yaar' => true, 'bhai' => true
        ];

        $words = explode(' ', strtolower($text));
        foreach ($words as $word) {
            if (isset($hinglishMarkers[$word])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Maps informal Hinglish commands to system actions.
     */
    public function mapIntent(string $text): string {
        static $pattern = '/(?<ACTION_FIX>theek|fix|shi|thik|banao|update)|(?<ACTION_INSPECT>dikhaye|check|check kr|analyze)|(?<QUERY_INFORMATIONAL>batao|pucho|query|sawal)/u';
        if (preg_match($pattern, $text, $matches)) {
            if (isset($matches['ACTION_FIX']) && $matches['ACTION_FIX'] !== '') return 'ACTION_FIX';
            if (isset($matches['ACTION_INSPECT']) && $matches['ACTION_INSPECT'] !== '') return 'ACTION_INSPECT';
            if (isset($matches['QUERY_INFORMATIONAL']) && $matches['QUERY_INFORMATIONAL'] !== '') return 'QUERY_INFORMATIONAL';
        }
        return 'UNKNOWN';
    }
}
