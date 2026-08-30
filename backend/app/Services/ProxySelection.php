<?php

namespace App\Services;

readonly class ProxySelection
{
    public function __construct(
        public string $proxy,
        public string $userAgent,
    ) {}
}
