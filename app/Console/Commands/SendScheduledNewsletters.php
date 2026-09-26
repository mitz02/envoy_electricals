<?php

namespace App\Console\Commands;

use App\Services\NewsletterService;
use Illuminate\Console\Command;

class SendScheduledNewsletters extends Command
{
    protected $signature = 'newsletter:send-scheduled';

    protected $description = 'Send all newsletter campaigns that are due to be dispatched';

    public function handle(NewsletterService $service): int
    {
        $result = $service->sendScheduled();

        if (empty($result['sent_newsletters'])) {
            $this->info('No scheduled newsletters were due.');

            return self::SUCCESS;
        }

        $this->info("Dispatched {$result['sent_newsletters']} of {$result['total']} scheduled newsletter(s).");

        if (($result['failed'] ?? 0) > 0) {
            $this->warn("{$result['failed']} newsletter(s) failed to reach any subscriber and remain scheduled.");
        }

        return self::SUCCESS;
    }
}
