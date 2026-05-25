<?php
namespace Core\Tools\Safety;

class NeuralSafetyGuard {
    // Combined regex pattern for O(1) matching instead of iterating through an array
    private string $compiledBlockedPattern = '/\brm\s+-rf\b|\brmdir\b|\brd\s+\/s\b|\bdel\s+\/[fsq]\b|\bformat\b|\bshutdown\b|\breboot\b|\bmkfs\b|\bdd\s+if=|\bdiskpart\b|\breg\s+(delete|add)\b|\bgit\s+reset\s+--hard\b|\bgit\s+clean\s+-fd\b|\bRemove-Item\b.*\s-Recurse\b|\bSet-ExecutionPolicy\b|\bwget\b|\bcurl\b|\b(?:bash|sh)\s*$/i';

    public function isCommandSafe(string $command): bool {
        $command = trim($command);
        if ($command === '') {
            return false;
        }

        if (preg_match($this->compiledBlockedPattern, $command)) {
            return false;
        }

        return true;
    }

    public function isPathSafe(string $path, string $root): bool {
        $path = $this->normalize($path);
        $root = rtrim($this->normalize($root), DIRECTORY_SEPARATOR);

        return $path === $root || str_starts_with(strtolower($path), strtolower($root . DIRECTORY_SEPARATOR));
    }

    private function normalize(string $path): string {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $prefix = '';

        if (preg_match('/^[A-Za-z]:/', $path, $m)) {
            $prefix = strtoupper($m[0]);
            $path = substr($path, 2);
        }

        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return $prefix . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
    }
}
