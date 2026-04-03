<?php

namespace App\Actions\Server;

use App\DTOs\SocketEventDTO;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Exceptions\SSHConnectionError;
use App\Exceptions\SSHError;
use App\Facades\Notifier;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use App\Notifications\ServerInstallationSucceed;
use App\ServerProviders\Custom;
use App\Services\PHP\PHP;
use Illuminate\Support\Sleep;

class InstallServer
{
    private const MAX_WAIT_SECONDS = 180;

    private const RETRY_INTERVAL_SECONDS = 10;

    protected Server $server;

    /**
     * @throws SSHError
     */
    public function run(Server $server): void
    {
        $this->server = $server;

        $this->waitForProvider();
        $this->waitForSsh();

        $this->install();
        $this->server->update([
            'status' => ServerStatus::READY,
        ]);
        Notifier::send($this->server, new ServerInstallationSucceed($this->server));
    }

    /**
     * @throws SSHConnectionError
     */
    protected function waitForProvider(): void
    {
        $remainingWait = self::MAX_WAIT_SECONDS;

        while ($remainingWait > 0) {
            if ($this->server->provider()->isRunning()) {
                return;
            }

            Sleep::sleep(self::RETRY_INTERVAL_SECONDS);
            $remainingWait -= self::RETRY_INTERVAL_SECONDS;
            $this->server->refresh();
        }

        throw new SSHConnectionError('Timed out waiting for the server provider to report the server as running.');
    }

    /**
     * @throws SSHConnectionError
     */
    protected function waitForSsh(): void
    {
        $remainingWait = self::MAX_WAIT_SECONDS;

        while ($remainingWait > 0) {
            try {
                $this->server->ssh()->connect();

                return;
            } catch (SSHConnectionError) {
                Sleep::sleep(self::RETRY_INTERVAL_SECONDS);
                $remainingWait -= self::RETRY_INTERVAL_SECONDS;
                $this->server->refresh();
            }
        }

        throw new SSHConnectionError('Timed out waiting for SSH to become available.');
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->createUser();
        $this->progress(15, 'installing-updates');
        $this->server->os()->upgrade();
        $this->progress(25, 'installing-dependencies');
        $this->server->os()->installDependencies();
        $services = $this->server->services;
        $currentProgress = 45;
        $progressPerService = count($services) ? (100 - $currentProgress) / count($services) : 0;
        foreach ($services as $service) {
            $currentProgress += $progressPerService;
            $this->progress($currentProgress, 'installing- '.$service->name);

            $service->newLog();

            $service->handler()->install();
            $service->update(['status' => ServiceStatus::READY]);
            if ($service->type == 'php') {
                $this->progress($currentProgress, 'installing-composer');
                /** @var PHP $handler */
                $handler = $service->handler();
                $handler->installComposer();
            }
        }
        $this->progress(100, 'finishing');
    }

    /**
     * @throws SSHError
     */
    protected function createUser(): void
    {
        // For custom servers, clear all existing keys after deploying the unique key
        $clearKeys = $this->server->provider === Custom::id();

        $this->server->os()->createUser(
            $this->server->authentication['user'],
            $this->server->authentication['pass'],
            $this->server->sshKey()['public_key'],
            $clearKeys
        );

        $this->server->ssh_user = config('core.ssh_user');
        $this->server->save();
        $this->server->refresh();
        $this->server->public_key = $this->server->os()->getPublicKey($this->server->getSshUser());
        $this->server->save();
    }

    protected function progress(int|float $percentage, ?string $step = null): void
    {
        $this->server->progress = $percentage;
        $this->server->progress_step = $step;
        $this->server->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'server.updated',
            data: new ServerResource($this->server),
        ));
    }
}
