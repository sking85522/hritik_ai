<?php
namespace Core\NLP\Intents;

/**
 * HRITIK AI - SEMANTIC INTENT MAPPER (DYNAMIC EDITION)
 * 
 * Identifies user intent using patterns retrieved from the neural knowledge base.
 */
class IntentMapper {
    
    /** @var array Shared cache for intent patterns */
    private static array $intentCache = [];

    /**
     * Maps a prompt to the most likely intent based on neural patterns.
     * 
     * @param string $text Cleaned user input
     * @return string Detected intent slug
     */
    public function map(string $text): string {
        $intents = $this->getIntents();
        
        foreach ($intents as $intentName => $patterns) {
            foreach ($patterns as $pattern) {
                // Using word boundaries for better accuracy
                $regex = '/\b' . preg_quote($pattern, '/') . '\b/i';
                if (preg_match($regex, $text)) return $intentName;
            }
        }
        
        return 'general_chat';
    }

    /**
     * Fetches intent patterns from the Knowledge Base (DB).
     * 
     * @return array Map of intent slugs to pattern arrays
     */
    private function getIntents(): array {
        if (!empty(self::$intentCache)) return self::$intentCache;

        global $db;
        
        if (!isset($db) || $db === null) {
            // Fallback to basic intents if DB is not available
            return [
                'greeting' => ['hi', 'hello', 'hey', 'namaste', 'hlo'],
                'identity' => ['who are you', 'kaun ho', 'tera naam'],
                'tool_use' => ['run command', 'execute', 'read file', 'check code', 'scan project', 'system check', 'calculate', 'solve', 'plus', 'minus', 'multiply']
            ];
        }

        $results = $db->query("SELECT sub_category, k_key FROM neural_knowledge WHERE category = 'intent'");
        
        if (isset($results['status']) && $results['status'] === 'success' && isset($results['data'])) {
            foreach ($results['data'] as $row) {
                $intent = $row['sub_category'];
                $pattern = $row['k_key'];
                
                if (!isset(self::$intentCache[$intent])) self::$intentCache[$intent] = [];
                self::$intentCache[$intent][] = $pattern;
            }
        }

        return self::$intentCache;
    }
}
