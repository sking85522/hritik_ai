<?php
namespace Core\NLP\Tone;

/**
 * HRITIK AI - POLITENESS & RESPECT DETECTOR
 * Analyzes the social tone and respect level of the user's input.
 */
class PolitenessDetector {
    
    /**
     * Detects the respect level (Aap vs Tu vs Neutral).
     */
    public function detect(string $text): string {
        static $pattern = '/(?<formal_respectful>aap|please|kripya|sir|ji)|(?<informal_aggressive>tu|tera|abe)/i';
        if (preg_match($pattern, $text, $matches)) {
            if (isset($matches['formal_respectful']) && $matches['formal_respectful'] !== '') return 'formal_respectful';
            if (isset($matches['informal_aggressive']) && $matches['informal_aggressive'] !== '') return 'informal_aggressive';
        }
        return 'neutral_casual';
    }

    /**
     * Calculates a politeness score (0 to 1).
     */
    public function score(string $text): float {
        $detected = $this->detect($text);
        if ($detected === 'formal_respectful') return 0.9;
        if ($detected === 'informal_aggressive') return 0.2;
        return 0.5;
    }
}
