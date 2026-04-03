<?php

namespace App\Actions\WebSockets;

use Illuminate\Http\Request;

class ResolveWebSocketUrl
{
    /**
     * Resolve the public WebSocket URL for the given request path.
     */
    public function forRequest(Request $request, string $path): string
    {
        $baseUrl = config('app.ws_url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            $baseUrl = $request->getSchemeAndHttpHost();
        }

        return $this->buildUrl($baseUrl, $path);
    }

    /**
     * Resolve the allowed origins for the internal WebSocket server.
     *
     * @return array<int, string>
     */
    public function allowedOrigins(): array
    {
        /** @var array<int, string> $configuredOrigins */
        $configuredOrigins = array_values(array_filter(config('core.ws_allowed_origins', [])));
        if ($configuredOrigins !== []) {
            return $configuredOrigins;
        }

        $fallbackOrigins = [];

        foreach (array_filter([config('app.ws_url'), config('app.url')]) as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            $parts = parse_url($url);

            if (! is_array($parts) || ! isset($parts['host'])) {
                continue;
            }

            $host = $parts['host'];
            $port = $parts['port'] ?? null;
            $scheme = strtolower($parts['scheme'] ?? 'http');
            $httpScheme = in_array($scheme, ['https', 'wss'], true) ? 'https' : 'http';

            $fallbackOrigins[] = $this->buildOrigin($httpScheme, $host, $port);
            $fallbackOrigins[] = $this->buildOrigin('http', $host, 80);
            $fallbackOrigins[] = $this->buildOrigin('https', $host, 443);
        }

        return array_values(array_unique(array_filter($fallbackOrigins)));
    }

    private function buildUrl(string $baseUrl, string $path): string
    {
        $parts = parse_url($baseUrl);

        if (! is_array($parts) || ! isset($parts['host'])) {
            $basePath = '/'.ltrim($path, '/');

            return 'ws://localhost'.$basePath;
        }

        $scheme = strtolower($parts['scheme'] ?? 'http');
        $webSocketScheme = in_array($scheme, ['https', 'wss'], true) ? 'wss' : 'ws';
        $host = $parts['host'];
        $port = $parts['port'] ?? null;
        $basePath = trim((string) ($parts['path'] ?? ''), '/');
        $targetPath = trim($path, '/');
        $portSuffix = $this->portSuffix($webSocketScheme, $port);

        $urlPath = '/'.$targetPath;
        if ($basePath !== '') {
            $urlPath = '/'.$basePath.$urlPath;
        }

        return sprintf('%s://%s%s%s', $webSocketScheme, $host, $portSuffix, $urlPath);
    }

    private function buildOrigin(string $scheme, string $host, ?int $port = null): string
    {
        return sprintf('%s://%s%s', $scheme, $host, $this->portSuffix($scheme, $port));
    }

    private function portSuffix(string $scheme, ?int $port): string
    {
        if ($port === null) {
            return '';
        }

        $defaultPort = match ($scheme) {
            'https', 'wss' => 443,
            default => 80,
        };

        if ($port === $defaultPort) {
            return '';
        }

        return ':'.$port;
    }
}
