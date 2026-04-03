<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class IpsService
{
    private const BASE_URL = 'https://ips.usm.my';
    private const VIVA_VOCE_PAGE = '/index.php/download/viva-voce';

    /**
     * Latest announcements from IPS homepage.
     *
     * @return array<int, array{title:string,url:string,image:string,date:string}>
     */
    public function latestNews(int $limit = 5): array
    {
        $cacheKey = 'ips.news.latest.' . max(1, $limit);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($limit) {
            $response = Http::timeout(10)->retry(1, 300)->get(self::BASE_URL . '/');

            if (! $response->successful()) {
                throw new RuntimeException('Unable to fetch updates from IPS USM right now.');
            }

            $items = $this->extractNewsItems($response->body(), $limit);

            if ($items === []) {
                throw new RuntimeException('No latest IPS announcement links were detected.');
            }

            return $items;
        });
    }

    /**
     * Viva Voce downloads list.
     *
     * @return array<int, array{title:string,url:string,section?:string}>
     */
    public function vivaVoceDownloads(int $limit = 15): array
    {
        $limit = max(1, $limit);
        $cacheKey = 'ips.downloads.viva-voce.v2.' . $limit;

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($limit) {
            $pageUrl = self::BASE_URL . self::VIVA_VOCE_PAGE;
            $response = Http::timeout(15)->retry(1, 300)->get($pageUrl);

            if (! $response->successful()) {
                throw new RuntimeException('Unable to fetch Viva Voce downloads from IPS USM right now.');
            }

            $items = $this->extractVivaVoceLinks($response->body(), $limit);

            if ($items === []) {
                throw new RuntimeException('No Viva Voce download files were detected.');
            }

            return $items;
        });
    }

    public function isAllowedVivaVoceDownloadUrl(string $url): bool
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return false;
        }

        try {
            $allowed = $this->vivaVoceDownloads(50);
        } catch (\Throwable) {
            return false;
        }

        foreach ($allowed as $item) {
            if (($item['url'] ?? null) === $url) {
                return true;
            }
        }

        return false;
    }

    public function isAllowedIpsUrl(string $url): bool
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return false;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return false;
        }

        return Str::contains(Str::lower($host), 'ips.usm.my');
    }

    /**
     * Convert a possibly-relative IPS link to absolute URL.
     */
    public function normalizeIpsUrl(string $href): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }

        if (Str::startsWith($href, '#') || Str::startsWith(Str::lower($href), 'javascript:')) {
            return null;
        }

        if (Str::startsWith($href, '/')) {
            return self::BASE_URL . $href;
        }

        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $this->isAllowedIpsUrl($href) ? $href : null;
        }

        return null;
    }

    /**
     * Like normalizeIpsUrl(), but permits absolute URLs to other hosts.
     */
    private function normalizeAnyHttpUrl(string $href): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }

        if (Str::startsWith($href, '#') || Str::startsWith(Str::lower($href), 'javascript:')) {
            return null;
        }

        if (Str::startsWith($href, '/')) {
            return self::BASE_URL . $href;
        }

        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        return null;
    }

    /**
     * @return array<int, array{title:string,url:string,image:string,date:string}>
     */
    private function extractNewsItems(string $html, int $limit): array
    {
        $structured = $this->extractStructuredNewsItems($html, $limit);
        if ($structured !== []) {
            return $structured;
        }

        $links = [];
        $seen = [];

        if (class_exists(\DOMDocument::class) && class_exists(\DOMXPath::class)) {
            $dom = new \DOMDocument();

            libxml_use_internal_errors(true);
            $loaded = $dom->loadHTML($html);
            libxml_clear_errors();

            if ($loaded !== false) {
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query('//a[@href]');

                if ($nodes !== false) {
                    foreach ($nodes as $node) {
                        $href = trim((string) $node->attributes?->getNamedItem('href')?->nodeValue);
                        $title = trim(preg_replace('/\s+/', ' ', (string) $node->textContent));

                        $candidate = $this->normalizeNewsLink($href, $title);
                        if (! $candidate) {
                            continue;
                        }

                        if (isset($seen[$candidate['url']])) {
                            continue;
                        }

                        $seen[$candidate['url']] = true;
                        $candidate['image'] = '';
                        $candidate['date'] = '';
                        $links[] = $candidate;

                        if (count($links) >= $limit) {
                            break;
                        }
                    }
                }
            }
        }

        if ($links !== []) {
            return $links;
        }

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $href = trim(strip_tags((string) ($match[1] ?? '')));
            $title = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($match[2] ?? ''))));

            $candidate = $this->normalizeNewsLink($href, $title);
            if (! $candidate) {
                continue;
            }

            if (isset($seen[$candidate['url']])) {
                continue;
            }

            $seen[$candidate['url']] = true;
            $candidate['image'] = '';
            $candidate['date'] = '';
            $links[] = $candidate;

            if (count($links) >= $limit) {
                break;
            }
        }

        return $links;
    }

    /**
     * @return array<int, array{title:string,url:string,image:string,date:string}>
     */
    private function extractStructuredNewsItems(string $html, int $limit): array
    {
        if (! class_exists(\DOMDocument::class) || ! class_exists(\DOMXPath::class)) {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        if ($loaded === false) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $containers = $xpath->query('//div[contains(@class, "el-item")]');

        if ($containers === false || $containers->length === 0) {
            return [];
        }

        $items = [];
        $seen = [];

        foreach ($containers as $container) {
            $titleNodes = $xpath->query('.//h6[contains(@class, "el-title")]//a', $container);
            if (! $titleNodes || $titleNodes->length === 0) {
                continue;
            }

            $titleNode = $titleNodes->item(0);
            $title = trim(preg_replace('/\s+/', ' ', (string) $titleNode->textContent));
            $href = trim((string) ($titleNode->attributes?->getNamedItem('href')?->nodeValue ?? ''));

            if ($title === '' || $href === '') {
                continue;
            }

            $url = $this->normalizeIpsUrl($href);
            if (! $url) {
                continue;
            }

            if (isset($seen[$url])) {
                continue;
            }

            $imgNodes = $xpath->query('.//img[contains(@class, "el-image")]', $container);
            $image = '';
            if ($imgNodes && $imgNodes->length > 0) {
                $imgSrc = trim((string) ($imgNodes->item(0)->attributes?->getNamedItem('src')?->nodeValue ?? ''));
                $imgUrl = $this->normalizeIpsUrl($imgSrc);
                if ($imgUrl) {
                    $image = $imgUrl;
                }
            }

            $dateNodes = $xpath->query('.//div[contains(@class, "el-meta")]', $container);
            $date = '';
            if ($dateNodes && $dateNodes->length > 0) {
                $date = trim((string) $dateNodes->item(0)->textContent);
            }

            $seen[$url] = true;
            $items[] = [
                'title' => $title,
                'url' => $url,
                'image' => $image,
                'date' => $date,
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return array{title:string,url:string}|null
     */
    private function normalizeNewsLink(string $href, string $title): ?array
    {
        $title = trim($title);
        if ($href === '' || $title === '') {
            return null;
        }

        $url = $this->normalizeIpsUrl($href);
        if (! $url) {
            return null;
        }

        $normalizedLower = Str::lower($title);

        $blacklist = [
            'home',
            'main page',
            'contact',
            'login',
            'read more',
        ];

        foreach ($blacklist as $bad) {
            if ($normalizedLower === $bad) {
                return null;
            }
        }

        return [
            'title' => $title,
            'url' => $url,
        ];
    }

    /**
     * @return array<int, array{title:string,url:string}>
     */
    private function extractVivaVoceLinks(string $html, int $limit): array
    {
        $items = [];
        $seen = [];

        // The IPS Viva-Voce page is an accordion with document links inside the accordion content.
        if (class_exists(\DOMDocument::class) && class_exists(\DOMXPath::class)) {
            $dom = new \DOMDocument();

            libxml_use_internal_errors(true);
            $loaded = $dom->loadHTML($html);
            libxml_clear_errors();

            if ($loaded !== false) {
                $xpath = new \DOMXPath($dom);

                $accordionItems = $xpath->query('//div[@uk-accordion]//div[contains(@class, "el-item")]');
                if ($accordionItems !== false && $accordionItems->length > 0) {
                    foreach ($accordionItems as $accordionItem) {
                        $section = '';
                        $sectionNode = $xpath->query('.//a[contains(@class, "uk-accordion-title")]', $accordionItem);
                        if ($sectionNode && $sectionNode->length > 0) {
                            $section = trim(preg_replace('/\s+/', ' ', (string) $sectionNode->item(0)->textContent));
                        }

                        $linkNodes = $xpath->query('.//div[contains(@class, "uk-accordion-content")]//a[@href]', $accordionItem);
                        if (! $linkNodes) {
                            continue;
                        }

                        foreach ($linkNodes as $node) {
                            $href = trim((string) $node->attributes?->getNamedItem('href')?->nodeValue);
                            $text = trim(preg_replace('/\s+/', ' ', (string) $node->textContent));

                            $url = $this->normalizeAnyHttpUrl($href);
                            if (! $url) {
                                continue;
                            }

                            if (isset($seen[$url])) {
                                continue;
                            }

                            $seen[$url] = true;

                            $title = $text !== '' ? $text : $url;
                            $title = trim($title);
                            if ($title === '') {
                                $title = $url;
                            }

                            $item = [
                                'title' => $title,
                                'url' => $url,
                            ];
                            if ($section !== '') {
                                $item['section'] = $section;
                            }

                            $items[] = $item;

                            if (count($items) >= $limit) {
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        if ($items !== []) {
            return $items;
        }

        // Fallback: extract only absolute http(s) links, and ignore IPS category nav links.
        preg_match_all('/https?:\\/\\/[^\s"\']+/i', $html, $matches);
        $urls = $matches[0] ?? [];

        foreach ($urls as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }

            if (Str::contains($url, self::BASE_URL . '/index.php/download/')) {
                continue;
            }

            if (isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $items[] = ['title' => $url, 'url' => $url];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }
}
