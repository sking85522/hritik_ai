<?php

namespace NumPHP\Core;

/**
 * Buffer handles raw data storage.
 */
class Buffer
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var DType
     */
    private $dtype;

    /**
     * Buffer constructor.
     * @param mixed $data
     * @param DType $dtype
     */
    public function __construct($data, DType $dtype)
    {
        $this->dtype = $dtype;
        $this->data = $this->castData($data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return int[]
     */
    public function getShape(): array
    {
        return $this->calculateShape($this->data);
    }

    /**
     * @param mixed $data
     * @return array
     */
    private function castData($data): array
    {
        if (is_array($data)) {
            return array_map([$this, 'castData'], $data);
        }

        settype($data, (string) $this->dtype);
        return $data;
    }

    /**
     * @param mixed $data
     * @return int[]
     */
    private function calculateShape($data): array
    {
        // ⚡ Bolt Optimization:
        // Replaced O(N²) recursive `array_merge` with an iterative O(N) loop.
        // Drops execution time significantly and prevents memory overhead
        // for deeply nested N-dimensional arrays.
        $shape = [];
        while (is_array($data)) {
            $shape[] = count($data);
            $data = $data[0] ?? null;
        }
        return $shape;
    }
}