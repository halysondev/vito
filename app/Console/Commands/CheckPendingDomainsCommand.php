<?php

namespace App\Console\Commands;

use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckPendingDomainsCommand extends Command
{
    protected $signature = 'domains:check-pending';

    protected $description = 'Check DNS resolution for pending hosted domains';

    public function handle(): void
    {
        if (! Schema::hasTable((new HostedDomain)->getTable())) {
            return;
        }

        HostedDomain::query()
            ->where('status', HostedDomainStatus::PENDING)
            ->where('updated_at', '>=', now()->subHours(24))
            ->cursor()
            ->each(function (HostedDomain $domain) {
                dispatch(new CheckDomainJob($domain))->onQueue('ssh');
            });
    }
}
