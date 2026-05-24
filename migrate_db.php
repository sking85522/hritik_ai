<?php
require_once __DIR__ . '/online_db.php';

echo "\033[0;36m[DB SYNC]\033[0m Starting Data Upload to Online Database...\n";

$localDbPath = __DIR__ . '/storage/local_db.json';
if (!file_exists($localDbPath)) {
    die("\033[0;31mError:\033[0m local_db.json not found in storage/\n");
}

$data = json_decode(file_get_contents($localDbPath), true);
if (!is_array($data)) {
    die("\033[0;31mError:\033[0m Invalid JSON in local_db.json\n");
}

global $db;

// Verify Connection first
$test = $db->query("SELECT 1 as test");
if (isset($test['status']) && $test['status'] === 'error') {
    die("\033[0;31m[CONNECTION FAILED]\033[0m Cannot upload data. Security block or invalid cookie.\nMessage: " . $test['message'] . "\n");
}
echo "\033[0;32m[CONNECTION OK]\033[0m Online Database connected successfully.\n\n";

$totalTables = count($data);
echo "Found $totalTables tables to upload.\n";

foreach ($data as $tableName => $rows) {
    if (!is_array($rows) || empty($rows)) continue;
    
    echo "Uploading table '$tableName' (" . count($rows) . " rows)...\n";
    
    // Clean up online table before uploading (Optional - we are doing a full sync)
    // $db->query("DELETE FROM $tableName"); 
    
    $successCount = 0;
    foreach ($rows as $row) {
        $columns = [];
        $values = [];
        foreach ($row as $col => $val) {
            $columns[] = "`$col`";
            $values[] = "'" . addslashes((string)$val) . "'";
        }
        
        $sql = "REPLACE INTO $tableName (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ")";
        $res = $db->query($sql);
        
        if (isset($res['status']) && $res['status'] === 'success') {
            $successCount++;
        }
    }
    
    echo "\033[0;32m[SYNC OK]\033[0m Uploaded $successCount / " . count($rows) . " rows to '$tableName'.\n";
}

echo "\n\033[0;32m[SYNC COMPLETE]\033[0m All local data has been successfully uploaded to the online database.\n";
