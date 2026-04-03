<?php

namespace App\Http\Controllers;

use App\Services\IpsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IpsDownloadController extends Controller
{
    public function download(Request $request, IpsService $ipsService)
    {
        $encoded = (string) $request->query('u', '');
        abort_unless($encoded !== '', 400, 'Missing download token.');

        $encodedName = (string) $request->query('n', '');

        $originalUrl = $this->base64UrlDecode($encoded);
        abort_unless($originalUrl !== null, 400, 'Invalid download token.');

        abort_unless($ipsService->isAllowedVivaVoceDownloadUrl($originalUrl), 403, 'This download is not allowed.');

        $fetchUrl = $this->buildFetchUrl($originalUrl);

        $tmpDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tmpDir);

        $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . 'ips_' . Str::random(24);

        $response = Http::timeout(60)
            ->retry(1, 500)
            ->withOptions(['sink' => $tmpPath])
            ->get($fetchUrl);

        abort_unless($response->successful(), 404, 'File is not available right now.');

        $preferredBaseName = $encodedName !== '' ? $this->base64UrlDecode($encodedName) : null;
        $filename = $this->buildFilename(
            $preferredBaseName,
            $originalUrl,
            $response->header('Content-Type'),
            $response->header('Content-Disposition')
        );

        $headers = [];
        $contentType = $response->header('Content-Type');
        if (is_string($contentType) && $contentType !== '') {
            $headers['Content-Type'] = $contentType;
        }

        return response()->download($tmpPath, $filename, $headers)->deleteFileAfterSend(true);
    }

    private function buildFetchUrl(string $url): string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        // SharePoint share links often render an HTML viewer by default.
        // Appending download=1 usually forces a file download response.
        if (! Str::contains($host, 'sharepoint.com')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return $url;
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $query['download'] = '1';

        $rebuilt = (string) ($parts['scheme'] ?? 'https');
        $rebuilt .= '://' . (string) ($parts['host'] ?? '');
        $rebuilt .= (string) ($parts['path'] ?? '');

        $rebuilt .= '?' . http_build_query($query);

        if (isset($parts['fragment']) && is_string($parts['fragment']) && $parts['fragment'] !== '') {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function base64UrlDecode(string $encoded): ?string
    {
        $encoded = trim($encoded);
        if ($encoded === '' || strlen($encoded) > 4096) {
            return null;
        }

        $b64 = strtr($encoded, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);
        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        return $decoded;
    }

    private function filenameFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $base = trim(basename($path));

        if ($base === '' || $base === '/' || $base === '.') {
            return 'ips-download';
        }

        // Basic sanitization
        $base = str_replace(['\\', '/', "\0"], '-', $base);

        return $base;
    }

    private function filenameFromContentDisposition(?string $contentDisposition): ?string
    {
        if (is_string($contentDisposition) && $contentDisposition !== '') {
            // RFC 5987 filename*
            if (preg_match("/filename\*\s*=\s*UTF-8''([^;]+)/i", $contentDisposition, $m)) {
                $name = rawurldecode(trim($m[1]));
                $name = trim($name, "\"' ");
                if ($name !== '') {
                    return $name;
                }
            }

            if (preg_match('/filename\s*=\s*"([^"]+)"/i', $contentDisposition, $m)) {
                $name = trim((string) $m[1]);
                if ($name !== '') {
                    return $name;
                }
            }

            if (preg_match('/filename\s*=\s*([^;\s]+)/i', $contentDisposition, $m)) {
                $name = trim((string) $m[1], "\"' ");
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return null;
    }

    private function buildFilename(?string $preferredBaseName, string $originalUrl, ?string $contentType, ?string $contentDisposition): string
    {
        $name = null;

        if (is_string($preferredBaseName) && trim($preferredBaseName) !== '') {
            $name = trim($preferredBaseName);
        }

        if ($name === null || $name === '') {
            $detected = $this->filenameFromContentDisposition($contentDisposition);
            if (is_string($detected) && $detected !== '') {
                $name = $detected;
            }
        }

        if ($name === null || $name === '') {
            $name = $this->filenameFromUrl($originalUrl);
        }

        $name = $this->sanitizeFilename($name);

        if ($name === '' || preg_match('/^[A-Za-z0-9_-]{20,}$/', pathinfo($name, PATHINFO_FILENAME) ?: '')) {
            $name = 'ips-viva-voce';
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if ($extension === '') {
            $guessed = $this->guessExtension($originalUrl, $contentType);
            if ($guessed !== '') {
                $name .= $guessed;
            }
        }

        // Reasonable length limit
        if (strlen($name) > 120) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $base = substr($base, 0, 110);
            $name = $ext !== '' ? ($base . '.' . $ext) : $base;
        }

        return $name;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = str_replace(["\0", '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $name);
        $name = preg_replace('/\s+/', ' ', $name) ?: $name;
        $name = trim($name, " .-\t\n\r\0\x0B");

        return $name;
    }

    private function guessExtension(string $url, ?string $contentType): string
    {
        $ct = is_string($contentType) ? Str::lower(trim(explode(';', $contentType)[0] ?? '')) : '';

        $map = [
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'application/vnd.ms-powerpoint' => '.ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
            'application/zip' => '.zip',
            'text/plain' => '.txt',
        ];

        if ($ct !== '' && isset($map[$ct])) {
            return $map[$ct];
        }

        // SharePoint shorthand: :b: is commonly PDF, :w: Word.
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
        if (Str::contains($path, '/:b:/')) {
            return '.pdf';
        }
        if (Str::contains($path, '/:w:/')) {
            return '.docx';
        }
        if (Str::contains($path, '/:x:/')) {
            return '.xlsx';
        }
        if (Str::contains($path, '/:p:/')) {
            return '.pptx';
        }

        return '';
    }
}
