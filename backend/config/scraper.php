<?php

return [

    // 127.0.0.1 rather than localhost so a down proxy service refuses instantly
    // instead of stalling ~2s on an IPv6 attempt before falling back.
    'proxy_service_url' => env('PROXY_SERVICE_URL', 'http://127.0.0.1:8081'),

    'proxy_timeout' => env('SCRAPER_PROXY_TIMEOUT', 2),

    // The scraper always uses the user-agent the Go service rotates (mimicking
    // proxy rotation). It only routes the request through the proxy itself when
    // this is on, i.e. when real, reachable proxies are configured.
    'use_proxy' => env('SCRAPER_USE_PROXY', false),

    'sitemap_url' => env('SCRAPER_SITEMAP_URL', 'https://www.rayashop.com/en-sitemap.xml'),

    'timeout' => env('SCRAPER_TIMEOUT', 15),
    'connect_timeout' => env('SCRAPER_CONNECT_TIMEOUT', 10),

    // Path to a CA bundle for HTTPS verification. The PHP built-in server does not
    // always pick up curl.cainfo from php.ini, so point Guzzle at it explicitly.
    // Leave null to use the system/php.ini default.
    'ca_bundle' => env('SCRAPER_CA_BUNDLE') ?: null,

    'delay_ms' => env('SCRAPER_DELAY_MS', 1000),

    'default_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',

    // One place to update when Jumia's markup changes.
    'selectors' => [
        'title' => 'h1.-fs20.-pts.-pbxs',
        'price' => 'span.-b.-ubpt.-tal.-fs24.-prxs',
        'image' => 'img.-fw',
        'product_link' => 'article.prd a.core',
    ],

];
