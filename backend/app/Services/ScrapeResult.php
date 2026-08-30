<?php

namespace App\Services;

readonly class ScrapeResult
{
    public function __construct(
        public ScrapeStatus $status,
        public ?string $message = null,
    ) {}
}
