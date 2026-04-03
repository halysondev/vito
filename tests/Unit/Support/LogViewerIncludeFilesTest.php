<?php

namespace Tests\Unit\Support;

use App\Support\LogViewerIncludeFiles;
use PHPUnit\Framework\TestCase;

class LogViewerIncludeFilesTest extends TestCase
{
    public function test_it_excludes_system_logs_by_default(): void
    {
        $files = LogViewerIncludeFiles::resolve();

        $this->assertContains('*.log', $files);
        $this->assertContains('**/*.log', $files);
        $this->assertArrayNotHasKey('/var/log/nginx/*', $files);
    }

    public function test_it_can_include_system_logs(): void
    {
        $files = LogViewerIncludeFiles::resolve(true);

        $this->assertSame('Nginx', $files['/var/log/nginx/*']);
        $this->assertSame('Apache', $files['/var/log/httpd/*']);
        $this->assertContains('/opt/homebrew/var/log/php-fpm.log', $files);
    }
}
