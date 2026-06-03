<?php
namespace Core\NLP\Entities;

/**
 * HRITIK AI - ENTITY RECOGNIZER (ONLINE DB EDITION)
 * Identifies entities using patterns stored in the remote neural database.
 */
class EntityRecognizer {
    
    private static array $entityCache = [];

    /**
     * Extracts entities from text using Online DB patterns.
     */
    public function extract(string $text): array {
        $patterns = $this->getPatterns();
        $found = [];

        foreach ($patterns as $type => $regexList) {
            if (empty($regexList)) continue;
            $escapedPatterns = array_map(function($p) { return preg_quote($p, '/'); }, $regexList);
            $regex = '/(' . implode('|', $escapedPatterns) . ')/i';
            if (preg_match_all($regex, $text, $matches)) {
                foreach ($matches[0] as $match) {
                    $found[] = ['type' => $type, 'value' => $match];
                }
            }
        }
        return $found;
    }

    /**
     * Fetches entity patterns from Online DB.
     */
    private function getPatterns(): array {
        if (!empty(self::$entityCache)) return self::$entityCache;

        if (file_exists(__DIR__ . '/../../../online_db.php')) {
            require_once __DIR__ . '/../../../online_db.php';
        }
        global $db;
        
        if (!isset($db) || $db === null) return [];

        $results = $db->query("SELECT sub_category, k_key FROM neural_knowledge WHERE category = 'entity'");
        
        if (isset($results['status']) && $results['status'] === 'success' && isset($results['data'])) {
            foreach ($results['data'] as $row) {
                $type = $row['sub_category'];
                $pattern = $row['k_key'];
                
                if (!isset(self::$entityCache[$type])) self::$entityCache[$type] = [];
                self::$entityCache[$type][] = $pattern;
            }
        }

        return self::$entityCache;
    }
}
