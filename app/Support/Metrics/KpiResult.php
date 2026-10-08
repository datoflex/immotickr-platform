<?php

namespace App\Support\Metrics;

final readonly class KpiResult
{
    public function __construct(
        public int $hits,
        public int $total,
        public int $pending = 0,
    ) {}

    /**
     * Share of hits in percent, or null while there is nothing to measure yet.
     */
    public function percentage(): ?float
    {
        if ($this->total === 0) {
            return null;
        }

        return $this->hits / $this->total * 100;
    }
}
