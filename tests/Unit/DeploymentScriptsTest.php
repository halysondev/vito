<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeploymentScriptsTest extends TestCase
{
    public function test_install_script_builds_assets_and_preserves_existing_execute_bits(): void
    {
        $script = $this->scriptContents('scripts/install.sh');

        $this->assertStringContainsString(
            "sudo -u vito bash -lc 'cd /home/vito/vito && npm ci && npm run build'",
            $script,
        );
        $this->assertStringContainsString(
            'rm -rf /home/vito/vito/node_modules /home/vito/vito/public/build',
            $script,
        );
        $this->assertStringContainsString('chmod -R u=rwX,go=rX /home/vito/vito', $script);
        $this->assertStringNotContainsString(
            'find /home/vito/vito -type f -exec chmod 644 {} \;',
            $script,
        );
    }

    public function test_update_script_rebuilds_frontend_assets(): void
    {
        $script = $this->scriptContents('scripts/update.sh');

        $this->assertStringContainsString(
            "sudo -u vito bash -lc 'cd /home/vito/vito && npm ci && npm run build'",
            $script,
        );
        $this->assertStringContainsString(
            'rm -rf /home/vito/vito/node_modules /home/vito/vito/public/build',
            $script,
        );
    }

    private function scriptContents(string $relativePath): string
    {
        $contents = file_get_contents($this->projectRoot().'/'.$relativePath);

        $this->assertNotFalse($contents);

        return $contents;
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
