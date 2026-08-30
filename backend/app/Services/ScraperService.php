<?php

namespace App\Services;

use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    private Client $client;

    /** @var array<string, string> */
    private array $selectors;

    public function __construct(private ProxyClient $proxy)
    {
        $this->client = new Client;
        $this->selectors = config('scraper.selectors');
    }

    public function scrape(string $url): ScrapeResult
    {
        try {
            $html = $this->get($url);
        } catch (RuntimeException $e) {
            Log::warning('Scrape request failed.', ['url' => $url, 'error' => $e->getMessage()]);
            return new ScrapeResult(ScrapeStatus::Failed, $e->getMessage());
        }

        $crawler = new Crawler($html, $url);
        $data = $this->extract($crawler, $url);

        if ($data['title'] === null || $data['title'] === '') {
            Log::info('Skipped: no product title found.', ['url' => $url]);
            return new ScrapeResult(ScrapeStatus::Skipped, 'no product title found');
        }

        $product = Product::updateOrCreate(
            ['source_url' => $url],
            [
                'title' => $data['title'],
                'price' => $data['price'],
                'image_url' => $data['image'],
            ],
        );

        return new ScrapeResult(
            $product->wasRecentlyCreated ? ScrapeStatus::Created : ScrapeStatus::Updated,
        );
    }

    /**
     *
     * @return array<int, string>
     */
    public function productLinksFrom(string $listingUrl): array
    {
        try {
            $html = $this->get($listingUrl);
        } catch (RuntimeException $e) {
            Log::warning('Could not fetch listing page.', ['url' => $listingUrl, 'error' => $e->getMessage()]);
            return [];
        }

        $links = (new Crawler($html, $listingUrl))
            ->filter($this->selectors['product_link'])
            ->each(fn (Crawler $node) => $this->absoluteUrl($listingUrl, $node->attr('href')));

        return array_values(array_unique(array_filter($links)));
    }


    public function sitemapUrls(string $sitemapUrl): array
    {
        try {
            $xml = $this->get($sitemapUrl);
        } catch (RuntimeException $e) {
            Log::warning('Could not fetch sitemap.', ['url' => $sitemapUrl, 'error' => $e->getMessage()]);
            return [];
        }

        preg_match_all('#<loc>\s*([^<]+?)\s*</loc>#i', $xml, $matches);
        $urls = array_values(array_unique($matches[1]));
        shuffle($urls);

        return $urls;
    }

    private function get(string $url): string
    {
        $selection = $this->proxy->nextProxy();
        $userAgent = $selection?->userAgent ?? config('scraper.default_user_agent');

        $options = [
            'headers' => ['User-Agent' => $userAgent],
            'timeout' => (float) config('scraper.timeout'),
            'connect_timeout' => (float) config('scraper.connect_timeout'),
            'verify' => config('scraper.ca_bundle') ?: true,
        ];
        // Rotate the user-agent always; route through the proxy only when real
        // proxies are configured (otherwise connect directly).
        if ($selection !== null && config('scraper.use_proxy')) {
            $options['proxy'] = $selection->proxy;
        }

        try {
            $response = $this->client->request('GET', $url, $options);
        } catch (GuzzleException $e) {
            if ($selection !== null) {
                $this->proxy->reportOutcome($selection->proxy, false);
            }
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        if ($selection !== null) {
            $this->proxy->reportOutcome($selection->proxy, true);
        }

        return (string) $response->getBody();
    }

    /**
     *
     * @return array{title: ?string, price: ?string, image: ?string}
     */
    private function extract(Crawler $crawler, string $pageUrl): array
    {
        $product = $this->jsonLdProduct($crawler);
        if ($product !== null) {
            return [
                'title' => isset($product['name']) ? trim((string) $product['name']) : null,
                'price' => $this->normalizePrice((string) ($this->jsonLdPrice($product) ?? '')),
                'image' => $this->jsonLdImage($product, $pageUrl),
            ];
        }

        $titleNode = $crawler->filter($this->selectors['title']);

        return [
            'title' => $titleNode->count() ? trim($titleNode->first()->text()) : null,
            'price' => $this->extractPrice($crawler),
            'image' => $this->extractImage($crawler, $pageUrl),
        ];
    }

    private function jsonLdProduct(Crawler $crawler): ?array
    {
        foreach ($crawler->filterXPath('//script[@type="application/ld+json"]') as $node) {
            $product = $this->findProductNode(json_decode($node->textContent, true));
            if ($product !== null) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Walk a decoded JSON-LD document (which may nest products under @graph or
     * arrays) and return the first node typed as a Product.
     */
    private function findProductNode(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }
        if (($data['@type'] ?? null) === 'Product') {
            return $data;
        }
        foreach ($data as $value) {
            if (is_array($value) && ($found = $this->findProductNode($value)) !== null) {
                return $found;
            }
        }

        return null;
    }

    private function jsonLdPrice(array $product): ?string
    {
        $offers = $product['offers'] ?? null;
        if (is_array($offers)) {
            return isset($offers['price'])
                ? (string) $offers['price']
                : (isset($offers[0]['price']) ? (string) $offers[0]['price'] : null);
        }

        return null;
    }

    private function jsonLdImage(array $product, string $pageUrl): ?string
    {
        $image = $product['image'] ?? null;
        if (is_array($image)) {
            $image = $image['url'] ?? ($image[0] ?? null);
        }

        return is_string($image) ? $this->absoluteUrl($pageUrl, $image) : null;
    }

    private function extractPrice(Crawler $crawler): ?string
    {
        $node = $crawler->filter($this->selectors['price']);
        if (! $node->count()) {
            return null;
        }

        return $this->normalizePrice($node->first()->text());
    }

    private function extractImage(Crawler $crawler, string $pageUrl): ?string
    {
        $node = $crawler->filter($this->selectors['image']);
        if (! $node->count()) {
            return null;
        }

        // Jumia lazy-loads images, so the real source is usually in data-src.
        $src = $node->first()->attr('data-src') ?? $node->first()->attr('src');

        return $this->absoluteUrl($pageUrl, $src);
    }

    private function normalizePrice(string $raw): ?string
    {
        if (! preg_match('/[0-9][0-9,]*(\.[0-9]+)?/', $raw, $matches)) {
            return null;
        }

        $cleaned = str_replace(',', '', $matches[0]);

        return is_numeric($cleaned) ? $cleaned : null;
    }

    private function absoluteUrl(string $base, ?string $ref): ?string
    {
        if ($ref === null || $ref === '') {
            return null;
        }

        return (string) UriResolver::resolve(new Uri($base), new Uri($ref));
    }
}
