<?php
/**
 * HRITIK AI - LOCAL DATABASE COMPATIBILITY LAYER
 *
 * The engine expects a global $db object with a query($sql) method.  When the
 * original online database file is missing or unavailable, this lightweight
 * JSON-backed adapter keeps console.php and api.php running.
 */

class HritikLocalDB {
    private string $path;
    private array $data = [
        'neural_memory' => [],
        'neural_knowledge' => [],
        'neural_history' => [],
        'neural_feedback_log' => [],
    ];

    public function __construct(?string $path = null) {
        $this->path = $path ?: __DIR__ . '/storage/local_db.json';
        $this->load();
    }

    public function query(string $sql): array {
        $sql = trim($sql);
        if ($sql === '') {
            return ['status' => 'success', 'data' => []];
        }

        try {
            if (preg_match('/^SELECT\s+COUNT\(\*\)\s+as\s+total\s+FROM\s+(\w+)/i', $sql, $m)) {
                $table = $m[1];
                return ['status' => 'success', 'data' => [['total' => count($this->data[$table] ?? [])]]];
            }

            if (preg_match('/^SELECT\s+/i', $sql)) {
                return $this->select($sql);
            }

            if (preg_match('/^(INSERT|REPLACE)\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\((.+)\)/is', $sql, $m)) {
                return $this->insert($m[2], $this->columns($m[3]), $this->values($m[4]));
            }

            if (preg_match('/^UPDATE\s+(\w+)\s+SET\s+(.+?)\s+WHERE\s+(.+?)(?:\s+LIMIT\s+\d+)?$/is', $sql, $m)) {
                return $this->update($m[1], $this->assignments($m[2]), $m[3]);
            }

            return ['status' => 'success', 'data' => [], 'affected_rows' => 0];
        } catch (Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage(), 'data' => []];
        }
    }

    private function select(string $sql): array {
        $table = null;
        if (preg_match('/\sFROM\s+(\w+)/i', $sql, $m)) {
            $table = $m[1];
        }
        if (!$table || !isset($this->data[$table])) {
            return ['status' => 'success', 'data' => []];
        }

        $rows = $this->data[$table];
        if (preg_match("/category\s*=\s*'([^']+)'/i", $sql, $m)) {
            $rows = array_values(array_filter($rows, fn($row) => (string)($row['category'] ?? '') === $m[1]));
        }
        if (preg_match("/category\s*!=\s*'([^']+)'/i", $sql, $m)) {
            $rows = array_values(array_filter($rows, fn($row) => (string)($row['category'] ?? '') !== $m[1]));
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
        $row['id'] = $this->nextId($table);
        $this->data[$table][] = $row;
        $this->save();
        return ['status' => 'success', 'data' => [], 'affected_rows' => 1, 'insert_id' => $row['id']];
    }

    private function update(string $table, array $assignments, string $where): array {
        if (!isset($this->data[$table])) {
            return ['status' => 'success', 'data' => [], 'affected_rows' => 0];
        }

        $affected = 0;
        foreach ($this->data[$table] as &$row) {
            if ($this->matchesWhere($row, $where)) {
                foreach ($assignments as $key => $value) {
                    $row[$key] = $value;
                }
                $affected++;
            }
        }
        unset($row);

        if ($affected > 0) {
            $this->save();
        }
        return ['status' => 'success', 'data' => [], 'affected_rows' => $affected];
    }

    private function matchesWhere(array $row, string $where): bool {
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
        foreach ($this->data[$table] ?? [] as $row) {
            $max = max($max, (int)($row['id'] ?? 0));
        }
        return $max + 1;
    }

    private function load(): void {
        if (!is_file($this->path)) {
            return;
        }

        $loaded = json_decode((string)file_get_contents($this->path), true);
        if (is_array($loaded)) {
            $this->data = array_replace($this->data, $loaded);
        }
    }

    private function save(): void {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->path, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

$db = $db ?? new HritikLocalDB();
