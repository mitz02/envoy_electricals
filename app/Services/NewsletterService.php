<?php

namespace App\Services;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterService
{
    /**
     * Send a newsletter to every subscribed subscriber.
     *
     * @param  bool  $markSent  Update status/sent_at once delivery succeeds.
     * @return array{delivered: int, failed: int, failures: array<int, string>, total: int}
     */
    public function send(Newsletter $newsletter, bool $markSent = true): array
    {
        $delivered = 0;
        $failed = 0;
        $failures = [];
        $total = 0;

        NewsletterSubscriber::query()
            ->where('status', 'subscribed')
            ->orderBy('id')
            ->chunkById(200, function ($recipients) use (&$delivered, &$failed, &$failures, &$total, $newsletter) {
                foreach ($recipients as $subscriber) {
                    $total++;

                    if (empty($subscriber->token)) {
                        $subscriber->token = Str::random(40);
                        $subscriber->save();
                    }

                    try {
                        Mail::to($subscriber->email, $subscriber->name)
                            ->send(new NewsletterMail($newsletter, $subscriber));
                        $delivered++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $failures[] = $subscriber->email;

                        Log::error('Newsletter delivery failed', [
                            'newsletter_id' => $newsletter->id,
                            'subscriber' => $subscriber->email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        if ($markSent && $delivered > 0) {
            $this->markSent($newsletter);
        }

        return [
            'delivered' => $delivered,
            'failed' => $failed,
            'failures' => $failures,
            'total' => $total,
        ];
    }

    public function markSent(Newsletter $newsletter): void
    {
        $newsletter->update([
            'status' => 'sent',
            'sent_at' => now(),
            'scheduled_at' => null,
        ]);
    }

    /**
     * Dispatch every overdue scheduled newsletter.
     */
    public function sendScheduled(): array
    {
        $scheduled = Newsletter::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($scheduled as $newsletter) {
            $result = $this->send($newsletter);

            if ($result['delivered'] > 0) {
                $sent++;
            } else {
                $failed++;
                Log::warning('Scheduled newsletter delivered to no subscribers', [
                    'newsletter_id' => $newsletter->id,
                    'failed' => $result['failed'],
                ]);
            }
        }

        return ['sent_newsletters' => $sent, 'total' => $scheduled->count(), 'failed' => $failed];
    }
}
