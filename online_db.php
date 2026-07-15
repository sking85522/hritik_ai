<?php

require_once __DIR__ . '/core/Memory/Storage/FirebaseDB.php';

$credentialsPath = __DIR__ . '/firebase_credentials.json';
$firebaseUrl = 'https://hritikai-default-rtdb.firebaseio.com/';

// Replace old logic globally with FirebaseDB instance
if (!isset($db)) {
    try {
        $db = new FirebaseDB($credentialsPath, $firebaseUrl);
    } catch (Exception $e) {
        // Fallback for fatal startup crash if no internet or file missing
        die("Firebase Initialization Failed: " . $e->getMessage());
    }
}
