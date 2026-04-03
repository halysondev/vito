<?php

namespace App\Support;

class LogViewerIncludeFiles
{
    /**
     * @return array<int|string, string>
     */
    public static function resolve(bool $includeSystemLogs = false): array
    {
        return [
            '*.log',
            '**/*.log',
            ...($includeSystemLogs ? self::systemLogs() : []),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    private static function systemLogs(): array
    {
        return [
            '/var/log/httpd/*' => 'Apache',
            '/var/log/nginx/*' => 'Nginx',
            '/opt/homebrew/var/log/nginx/*',
            '/opt/homebrew/var/log/httpd/*',
            '/opt/homebrew/var/log/php-fpm.log',
            '/opt/homebrew/var/log/postgres*log',
            '/opt/homebrew/var/log/redis*log',
            '/opt/homebrew/var/log/supervisor*log',
        ];
    }
}
