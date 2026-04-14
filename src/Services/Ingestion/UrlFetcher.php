<?php

namespace LarAIgent\AiKit\Services\Ingestion;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class UrlFetcher
{
    /**
     * Fetch URL content with security validation.
     *
     * @return array{body: string, content_type: string, url: string}
     */
    public function fetch(string $url): array
    {
        $this->validateUrl($url);

        $timeout = (int) config('larai-kit.url_ingestion.timeout', 30);
        $maxRedirects = (int) config('larai-kit.url_ingestion.max_redirects', 5);
        $maxSizeMb = (int) config('larai-kit.url_ingestion.max_size_mb', 10);
        $userAgent = config('larai-kit.url_ingestion.user_agent', 'LarAIgent/1.0');

        $response = Http::withHeaders([
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/pdf,text/plain,text/markdown,*/*',
        ])->withOptions([
            'allow_redirects' => ['max' => $maxRedirects],
        ])->timeout($timeout)->get($url);

        if ($response->failed()) {
            throw new RuntimeException(
                "Failed to fetch URL [{$response->status()}]: {$url}"
            );
        }

        $body = $response->body();
        $sizeBytes = strlen($body);
        $maxBytes = $maxSizeMb * 1024 * 1024;

        if ($sizeBytes > $maxBytes) {
            throw new RuntimeException(
                "URL content exceeds maximum size of {$maxSizeMb}MB ({$sizeBytes} bytes)."
            );
        }

        $contentType = strtolower(explode(';', $response->header('Content-Type') ?? 'text/html')[0]);

        return [
            'body' => $body,
            'content_type' => trim($contentType),
            'url' => $url,
        ];
    }

    /**
     * Validate URL is safe to fetch (SSRF protection).
     */
    protected function validateUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (! $parsed || ! isset($parsed['host'])) {
            throw new RuntimeException("Invalid URL: {$url}");
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'])) {
            throw new RuntimeException("Blocked URL scheme: {$scheme}. Only http and https are allowed.");
        }

        $host = $parsed['host'];

        // Resolve DNS to check for private IPs
        $ip = gethostbyname($host);

        if ($ip === $host && ! filter_var($host, FILTER_VALIDATE_IP)) {
            throw new RuntimeException("Could not resolve hostname: {$host}");
        }

        if ($this->isPrivateIp($ip)) {
            throw new RuntimeException(
                "Blocked: URL resolves to private/reserved IP address ({$ip}). "
                . "SSRF protection prevents access to internal networks."
            );
        }
    }

    /**
     * Check if an IP address is in a private or reserved range.
     */
    protected function isPrivateIp(string $ip): bool
    {
        // IPv4 private/reserved ranges
        $blocked = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '0.0.0.0/8',
        ];

        foreach ($blocked as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        // IPv6 loopback and private
        if (in_array($ip, ['::1', '::'])) {
            return true;
        }

        return false;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
