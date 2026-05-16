<?php
namespace Core\Training\Auto;

use Core\SQLGenerator\SQLGenerator;

/**
 * HRITIK AI - NEURAL DATABASE REFACTORER
 * Audits, categorizes, and splits the main knowledge base into specialized neural tables.
 */
class NeuralDatabaseRefactorer {
    
    private array $specializedTables = [
        'coding' => 'kb_coding',
        'social' => 'kb_social',
        'science' => 'kb_science',
        'history' => 'kb_history',
        'trash' => 'kb_trash_bin'
    ];

    /**
     * Start the audit and refactor process in batches.
     */
    public function refactorBatch(int $limit = 500): string {
        global $db;
        if (!isset($db) || $db === null) return "Database offline.";

        $this->ensureTablesExist();

        // 1. Fetch unorganized seeds
        $sql = "SELECT * FROM neural_knowledge WHERE category = 'verified_qa' LIMIT $limit";
        $res = $db->query($sql);
        
        if (empty($res['data'])) return "Audit complete. No more unorganized seeds found.";

        $count = 0;
        foreach ($res['data'] as $row) {
            $category = $this->autoCategorize($row['k_key'], $row['k_value']);
            $targetTable = $this->specializedTables[$category] ?? 'neural_knowledge';
            
            if ($targetTable !== 'neural_knowledge') {
                $this->moveToTable($row, $targetTable);
                $count++;
            }
        }

        return "[AUDIT] Processed $count seeds. Categorized into specialized neural clusters.";
    }

    private function autoCategorize(string $key, string $val): string {
        $text = strtolower($key . ' ' . $val);
        
        // Trash Detection
        if (strlen($val) < 3 || str_contains($text, 'n/a') || str_contains($text, 'test data')) return 'trash';
        
        // Coding
        if (preg_match('/(code|php|python|java|html|function|class|<?php|def )/i', $text)) return 'coding';
        
        // Social
        if (preg_match('/(kaise ho|hello|hi|naam|hritik|kese ho)/i', $text)) return 'social';
        
        // Default
        return 'science';
    }

    private function moveToTable(array $row, string $table): void {
        global $db;
        $k = addslashes($row['k_key']);
        $v = addslashes($row['k_value']);
        
        // Insert into new table
        $db->query("INSERT INTO $table (k_key, k_value) VALUES ('$k', '$v')");
        
        // Remove from main table to mark as processed
        $db->query("DELETE FROM neural_knowledge WHERE id = " . $row['id']);
    }

    private function ensureTablesExist(): void {
        global $db;
        foreach ($this->specializedTables as $table) {
            $db->query("CREATE TABLE IF NOT EXISTS $table (id INT AUTO_INCREMENT PRIMARY KEY, k_key TEXT, k_value LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        }
    }
}
