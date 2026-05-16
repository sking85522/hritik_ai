<?php
namespace Core\NLP\Cleaning;

/**
 * HRITIK AI - NEURAL AUTO-SPELLER
 * Corrects common Hinglish spelling variations using the remote neural database.
 */
class AutoSpeller {
    
    private static array $spellingCache = [];

    /**
     * Corrects spelling variations in the input text.
     */
    public function correct(string $text): string {
        $corrections = $this->getCorrections();
        foreach ($corrections as $wrong => $right) {
            $text = preg_replace('/\b' . $wrong . '\b/i', $right, $text);
        }
        return $text;
    }

    /**
     * Fetches spelling corrections from Online DB.
     */
    private function getCorrections(): array {
        if (!empty(self::$spellingCache)) return self::$spellingCache;

        require_once __DIR__ . '/../../../online_db.php';
        global $db;
        
        $results = $db->query("SELECT k_key, k_value FROM neural_knowledge WHERE category = 'spelling'");
        
        if (isset($results['status']) && $results['status'] === 'success' && isset($results['data'])) {
            foreach ($results['data'] as $row) {
                self::$spellingCache[$row['k_key']] = $row['k_value'];
            }
        }

        return self::$spellingCache;
    }
}
