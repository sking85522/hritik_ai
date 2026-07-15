<?php

class FirebaseDB {
    private string $databaseUrl;
    private array $credentials;
    private ?string $accessToken = null;
    private int $tokenExpiry = 0;

    // In-memory cache to simulate SQL-like behavior without constantly pulling full DB
    private array $localData = [];
    private bool $isDataLoaded = false;
    private string $cachePath;

    public function __construct(string $credentialsPath, string $databaseUrl, string $cachePath = __DIR__ . '/../../../storage/firebase_cache.json') {
        $this->databaseUrl = rtrim($databaseUrl, '/');
        $this->cachePath = $cachePath;

        if (!file_exists($credentialsPath)) {
            throw new Exception("Firebase credentials file not found: $credentialsPath");
        }

        $this->credentials = json_decode(file_get_contents($credentialsPath), true);
        if (!$this->credentials) {
            throw new Exception("Invalid Firebase credentials JSON.");
        }

        $this->loadCache();
    }

    private function loadCache(): void {
        if (file_exists($this->cachePath)) {
            $data = json_decode(file_get_contents($this->cachePath), true);
            if (is_array($data)) {
                $this->localData = $data;
            }
        }
    }

    private function saveCache(): void {
        $dir = dirname($this->cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->cachePath, json_encode($this->localData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function getAccessToken(): string {
        // If placeholder private key is used, do not attempt to get token
        if (str_contains($this->credentials['private_key'] ?? '', 'YOUR_PRIVATE_KEY_HERE')) {
            return 'dummy_token';
        }

        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $claim = json_encode([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $b64header = $this->base64url_encode($header);
        $b64claim = $this->base64url_encode($claim);

        $signature = '';
        openssl_sign($b64header . "." . $b64claim, $signature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256);
        $b64signature = $this->base64url_encode($signature);

        $jwt = $b64header . "." . $b64claim . "." . $b64signature;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new Exception("Failed to get Firebase access token: " . $response);
        }

        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = $now + ($data['expires_in'] ?? 3600) - 60; // Refresh 1 min before expiry
        return $this->accessToken;
    }

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Fetch all data from Firebase to sync local cache
     */
    public function syncFromFirebase(): void {
        if (str_contains($this->credentials['private_key'] ?? '', 'YOUR_PRIVATE_KEY_HERE')) {
             $this->isDataLoaded = true;
             return;
        }

        $token = $this->getAccessToken();
        $ch = curl_init($this->databaseUrl . '/.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (is_array($data)) {
            $this->localData = $data;
            $this->saveCache();
            $this->isDataLoaded = true;
        }
    }

    /**
     * Push a specific table or node to Firebase
     */
    public function pushToFirebase(string $path, array $data): void {
        if (str_contains($this->credentials['private_key'] ?? '', 'YOUR_PRIVATE_KEY_HERE')) {
            return;
        }

        $token = $this->getAccessToken();
        $ch = curl_init($this->databaseUrl . '/' . $path . '.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Push exactly one new row to Firebase (appends)
     */
    public function appendToFirebase(string $path, array $data): void {
        if (str_contains($this->credentials['private_key'] ?? '', 'YOUR_PRIVATE_KEY_HERE')) {
            return;
        }

        $token = $this->getAccessToken();
        // Use PUT to exact ID if we maintain numeric arrays, or POST for random hash.
        // Assuming we PUT to /table/ID.json to maintain structure.
        $ch = curl_init($this->databaseUrl . '/' . $path . '.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Simulate SQL queries over local cache, syncing to Firebase on writes
     */
    public function query(string $sql): array {
        if (!$this->isDataLoaded && empty($this->localData)) {
            $this->syncFromFirebase();
        }

        $sql = trim($sql);
        if ($sql === '') {
            return ['status' => 'success', 'data' => []];
        }

        if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+([a-zA-Z0-9_]+)(?:\s+WHERE\s+(.+?))?(?:\s+ORDER\s+BY\s+(.+?))?(?:\s+LIMIT\s+(\d+))?$/i', $sql, $matches)) {
            return $this->select($sql, $matches);
        }

        if (preg_match('/^INSERT\s+INTO\s+([a-zA-Z0-9_]+)\s*\((.+?)\)\s*VALUES\s*\((.+?)\)$/i', $sql, $matches)) {
            $table = $matches[1];
            $cols = $this->columns($matches[2]);
            $vals = $this->values($matches[3]);
            return $this->insert($table, $cols, $vals);
        }

        if (preg_match('/^UPDATE\s+([a-zA-Z0-9_]+)\s+SET\s+(.+?)(?:\s+WHERE\s+(.+?))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $assignments = $this->assignments($matches[2]);
            $where = $matches[3] ?? '';
            return $this->update($table, $assignments, $where);
        }

        // Aggregate functions and basic queries... (Fallbacks)
        if (preg_match('/^SELECT\s+COUNT\(\*\)\s+as\s+(\w+)\s+FROM\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
            $alias = $matches[1];
            $table = $matches[2];
            $count = isset($this->localData[$table]) ? count($this->localData[$table]) : 0;
            return ['status' => 'success', 'data' => [[$alias => $count]]];
        }

        return ['status' => 'error', 'message' => "Unsupported query: $sql"];
    }

    private function select(string $sql, array $matches): array {
        $table = $matches[2];

        if (preg_match('/FROM\s+([a-zA-Z0-9_]+)/i', $sql, $m)) {
            $table = $m[1];
        }

        if (!isset($this->localData[$table])) {
            return ['status' => 'success', 'data' => []];
        }

        $rows = $this->localData[$table];

        // Exact match filters (category = 'x')
        if (preg_match("/category\s*=\s*'([^']+)'/i", $sql, $m)) {
            $rows = array_values(array_filter($rows, fn($row) => (string)($row['category'] ?? '') === $m[1]));
        }

        // Exact match filters for sub_category
        if (preg_match("/sub_category\s*=\s*'([^']+)'/i", $sql, $m)) {
            $rows = array_values(array_filter($rows, fn($row) => (string)($row['sub_category'] ?? '') === $m[1]));
        }

        // Exclusion filters (category != 'x')
        if (preg_match_all("/category\s*!=\s*'([^']+)'/i", $sql, $excMatches)) {
            foreach ($excMatches[1] as $excludedCategory) {
                $rows = array_values(array_filter($rows, fn($row) => (string)($row['category'] ?? '') !== $excludedCategory));
            }
        }

        if (preg_match("/k_key\s*=\s*'([^']+)'/i", $sql, $m)) {
            $rows = array_values(array_filter($rows, fn($row) => (string)($row['k_key'] ?? '') === $m[1]));
        }

        $queryNeedles = $this->likeNeedles($sql);
        if ($queryNeedles) {
            $rows = array_values(array_filter($rows, function ($row) use ($queryNeedles) {
                $haystack = strtolower(implode(' ', array_map('strval', $row)));
                foreach ($queryNeedles as $needle) {
                    if ($needle !== '' && str_contains($haystack, strtolower($needle))) {
                        return true;
                    }
                }
                return false;
            }));
        }

        usort($rows, fn($a, $b) => ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0)));
        $limit = 50;
        if (preg_match('/\sLIMIT\s+(\d+)/i', $sql, $m)) {
            $limit = max(1, (int)$m[1]);
        }

        return ['status' => 'success', 'data' => array_slice($rows, 0, $limit)];
    }

    private function insert(string $table, array $columns, array $values): array {
        $row = [];
        foreach ($columns as $i => $column) {
            $row[$column] = $values[$i] ?? '';
        }

        if (!isset($this->localData[$table])) {
            $this->localData[$table] = [];
        }

        $row['id'] = $this->nextId($table);

        // Make sure it's zero indexed correctly or associative for Firebase
        $this->localData[$table][] = $row;

        // We push to ID as index so it matches an array format in Firebase
        $index = count($this->localData[$table]) - 1;
        $this->appendToFirebase($table . '/' . $index, $row);

        $this->saveCache();

        return ['status' => 'success', 'data' => [], 'affected_rows' => 1, 'insert_id' => $row['id']];
    }

    private function update(string $table, array $assignments, string $where): array {
        if (!isset($this->localData[$table])) {
            return ['status' => 'success', 'data' => [], 'affected_rows' => 0];
        }

        $affected = 0;
        foreach ($this->localData[$table] as $index => &$row) {
            if ($this->matchesWhere($row, $where)) {
                foreach ($assignments as $key => $value) {
                    $row[$key] = $value;
                }
                $affected++;
                // Push specific row update to Firebase
                $this->appendToFirebase($table . '/' . $index, $row);
            }
        }
        unset($row);

        if ($affected > 0) {
            $this->saveCache();
        }
        return ['status' => 'success', 'data' => [], 'affected_rows' => $affected];
    }

    private function matchesWhere(array $row, string $where): bool {
        if (trim($where) === '') return true;

        if (preg_match_all("/(\w+)\s*=\s*'([^']*)'/", $where, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ((string)($row[$match[1]] ?? '') !== $match[2]) {
                    return false;
                }
            }
        }
        return true;
    }

    private function columns(string $csv): array {
        return array_map(fn($col) => trim($col, " \t\n\r\0\x0B`"), explode(',', $csv));
    }

    private function values(string $csv): array {
        $values = str_getcsv($csv, ',', "'", "\\");
        return array_map(fn($value) => stripcslashes(trim($value)), $values);
    }

    private function assignments(string $csv): array {
        $result = [];
        foreach (str_getcsv($csv, ',', "'", "\\") as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $result[trim($key, " \t\n\r\0\x0B`")] = stripcslashes(trim($value, " \t\n\r\0\x0B'"));
            }
        }
        return $result;
    }

    private function likeNeedles(string $sql): array {
        preg_match_all("/LIKE\s+'%([^']+)%'/i", $sql, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    private function nextId(string $table): int {
        $max = 0;
        foreach ($this->localData[$table] ?? [] as $row) {
            if (is_array($row)) {
                $max = max($max, (int)($row['id'] ?? 0));
            }
        }
        return $max + 1;
    }
}
