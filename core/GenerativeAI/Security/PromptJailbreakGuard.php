<?php
namespace Core\GenerativeAI\Security;

/**
 * HRITIK AI - PROMPT JAILBREAK GUARD
 * Protects the generative core from malicious prompt injections or "jailbreak" attempts.
 */
class PromptJailbreakGuard {
    
    // Combined regex pattern for O(1) matching instead of iterating through an array
    private string $compiledPattern = '/ignore all previous instructions|you are now a|bypass your safety|system override|developer mode/i';

    /**
     * Scans the prompt for malicious intent.
     */
    public function isSafe(string $prompt): bool {
        if (preg_match($this->compiledPattern, $prompt)) {
            return false;
        }
        return true;
    }
}
