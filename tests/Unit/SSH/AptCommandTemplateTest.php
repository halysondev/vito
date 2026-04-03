<?php

namespace Tests\Unit\SSH;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AptCommandTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_template_uses_the_shared_apt_retry_wrapper(): void
    {
        $command = view('ssh.os.upgrade')->render();

        $this->assertStringContainsString('vito_retry_apt() {', $command);
        $this->assertStringContainsString('vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get update', $command);
    }

    public function test_service_install_template_uses_the_shared_apt_retry_wrapper(): void
    {
        $command = view('ssh.services.webserver.nginx.install-nginx')->render();

        $this->assertStringContainsString('vito_retry_apt() {', $command);
        $this->assertStringContainsString('vito_retry_apt sudo DEBIAN_FRONTEND=noninteractive apt-get install nginx -y', $command);
    }
}
