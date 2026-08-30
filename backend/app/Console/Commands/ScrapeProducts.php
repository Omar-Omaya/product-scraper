<?php

namespace App\Console\Commands;

use App\Services\ScraperService;
use App\Services\ScrapeStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products
        {urls?* : Product page URLs to scrape directly}
        {--listing= : A category/listing URL to pull product links from}
        {--sitemap= : An XML sitemap URL to pull product URLs from}
        {--limit=20 : Maximum number of products to store}
        {--delay= : Milliseconds to wait between requests}';

    protected $description = 'Scrape product pages and store them';

    public function handle(ScraperService $scraper): int
    {
        $urls = $this->collectUrls($scraper);
        if (empty($urls)) {
            $this->error('Nothing to scrape. Pass product URLs, a --listing URL, or a --sitemap URL.');
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $delayMs = (int) ($this->option('delay') ?? config('scraper.delay_ms'));

        $sitemapMode = (bool) $this->option('sitemap');

        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        $stored = 0;
        $attempts = 0;

        foreach ($urls as $url) {
            if ($sitemapMode && ($stored >= $limit || $attempts >= $limit * 10)) {
                break;
            }
            if ($delayMs > 0 && $attempts > 0) {
                usleep($delayMs * 1000);
            }
            $attempts++;

            $this->line("Scraping {$url}");

            try {
                $result = $scraper->scrape($url);
            } catch (\Throwable $e) {
                Log::error('Unexpected scrape error.', ['url' => $url, 'error' => $e->getMessage()]);
                $counts['failed']++;
                $this->error("  failed: {$e->getMessage()}");
                continue;
            }

            switch ($result->status) {
                case ScrapeStatus::Created:
                    $counts['created']++;
                    $stored++;
                    $this->info('  created');
                    break;
                case ScrapeStatus::Updated:
                    $counts['updated']++;
                    $stored++;
                    $this->line('  updated');
                    break;
                case ScrapeStatus::Skipped:
                    $counts['skipped']++;
                    $this->warn("  skipped: {$result->message}");
                    break;
                case ScrapeStatus::Failed:
                    $counts['failed']++;
                    $this->error("  failed: {$result->message}");
                    break;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. scraped %d, updated %d, skipped %d, failed %d.',
            $counts['created'], $counts['updated'], $counts['skipped'], $counts['failed'],
        ));

        return ($counts['created'] + $counts['updated']) > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function collectUrls(ScraperService $scraper): array
    {
        if ($sitemap = $this->option('sitemap')) {
            return $scraper->sitemapUrls($sitemap);
        }

        $urls = $this->argument('urls');

        if ($listing = $this->option('listing')) {
            $urls = array_merge($urls, $scraper->productLinksFrom($listing));
        }

        $urls = array_values(array_unique($urls));

        return array_slice($urls, 0, (int) $this->option('limit'));
    }
}
