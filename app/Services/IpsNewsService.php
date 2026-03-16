<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class IpsNewsService
{
    /**
     * @return array<int, array{title:string,url:string,image:string,date:string}>
     */
    public function latest(int $limit = 5): array
    {
        $cacheKey = 'ips.news.latest.' . max(1, $limit);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($limit) {
            return $this->fetchLatest($limit);
        });
    }

    /**
     * @return array<int, array{title:string,url:string,image:string,date:string}>
     */
    private function fetchLatest(int $limit): array
    {
        $response = Http::timeout(10)->retry(1, 300)->get('https://ips.usm.my/');

        if (! $response->successful()) {
            throw new RuntimeException('Unable to fetch updates from IPS USM right now.');
        }

        $items = $this->extractLinks($response->body(), $limit);

        if ($items === []) {
            throw new RuntimeException('No latest IPS announcement links were detected.');
        }

        return $items;
    }

    /**
     * @return array<int, array{title:string,url:string,image:string,date:string}>
     */
    private function extractLinks(string $html, int $limit): array
    {
        $structured = $this->extractStructuredItems($html, $limit);
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

                        $candidate = $this->normalizeLink($href, $title);
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

            $candidate = $this->normalizeLink($href, $title);
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
    private function extractStructuredItems(string $html, int $limit): array
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

            $url = $href;
            if (Str::startsWith($href, '/')) {
                $url = 'https://ips.usm.my' . $href;
            }
            if (! Str::startsWith($url, ['http://', 'https://'])) {
                continue;
            }

            if (isset($seen[$url])) {
                continue;
            }

            $imgNodes = $xpath->query('.//img[contains(@class, "el-image")]', $container);
            $image = '';
            if ($imgNodes && $imgNodes->length > 0) {
                $imgSrc = trim((string) ($imgNodes->item(0)->attributes?->getNamedItem('src')?->nodeValue ?? ''));
                if ($imgSrc !== '' && Str::startsWith($imgSrc, '/')) {
                    $image = 'https://ips.usm.my' . $imgSrc;
                } elseif (Str::startsWith($imgSrc, ['http://', 'https://'])) {
                    $image = $imgSrc;
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
    private function normalizeLink(string $href, string $title): ?array
    {
        if ($href === '' || $title === '') {
            return null;
        }

        if (Str::startsWith($href, '#') || Str::startsWith(Str::lower($href), 'javascript:')) {
            return null;
        }

        $url = $href;

        if (Str::startsWith($href, '/')) {
            $url = 'https://ips.usm.my' . $href;
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);
        if (! Str::contains($host, 'ips.usm.my')) {
            return null;
        }

        $normalizedTitle = trim($title);
        $normalizedLower = Str::lower($normalizedTitle);

        $blacklist = [
            'home',
            'main page',
            'contact',
            'login',
            'read more',
            'click here',
            'selanjutnya',
        ];

        if (mb_strlen($normalizedTitle) < 18 || in_array($normalizedLower, $blacklist, true)) {
            return null;
        }

        return [
            'title' => $normalizedTitle,
            'url' => $url,
        ];
    }
}
