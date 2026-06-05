<?php
namespace Core\Memory;

class MemorySafety {
    
    private array $blacklist = ['password', 'secret', 'key', 'token', 'credit card'];

    /**
     * Sanitizes a string before storage.
     */
    public function sanitize(string $content): string {
        // Optimization: Pass array directly to str_ireplace to leverage native C loop
        return str_ireplace($this->blacklist, '[REDACTED]', $content);
    }

    /**
     * Check if the content is safe to store.
     */
    public function isSafe(string $content): bool {
        // More complex logic can be added here
        return true; 
    }
}
