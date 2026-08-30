<?php

namespace App\Http\Controllers;

use App\Services\ScraperService;
use App\Services\ScrapeStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScrapeController extends Controller
{
    public function store(Request $request, ScraperService $scraper): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $limit = min(max($request->integer('limit', 8), 1), 20);

        // The sitemap holds ~24k URLs; cache the list so a 30s polling loop does
        // not re-download it on every request.
        $urls = Cache::remember(
            'scraper.sitemap_urls',
            now()->addMinutes(10),
            fn () => $scraper->sitemapUrls(config('scraper.sitemap_url')),
        );

        if ($q !== '') {
            $needle = strtolower($q);
            $urls = array_values(array_filter($urls, fn ($url) => str_contains(strtolower($url), $needle)));
        }
        shuffle($urls);

        $stored = 0;
        $attempts = 0;
        // Bounded so this stays a fast, synchronous request the 30s loop can call.
        $deadline = microtime(true) + 18.0;
        foreach ($urls as $url) {
            if ($stored >= $limit || $attempts >= $limit * 3 || microtime(true) > $deadline) {
                break;
            }
            $attempts++;

            if (in_array($scraper->scrape($url)->status, [ScrapeStatus::Created, ScrapeStatus::Updated], true)) {
                $stored++;
            }
        }

        return response()->json(['scraped' => $stored, 'query' => $q]);
    }
}
