<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NathanHeffley\LaravelSlackBlocks\Messages\SlackMessage;

class SendExpiredResources extends Notification
{
    public function __construct(protected $expiredResources)
    {
        //
    }

    public function via($notifiable)
    {
        return ['slack'];
    }

    public function toSlack($notifiable)
    {
        $msg = (new SlackMessage())
            ->from('Onramp', ':onramp:')
            ->content(sprintf('Here is your expired resources report: %s', url('/nova')));

        $this->expiredResources->each(function ($resource) use ($msg) {
            $msg->attachment(function ($attachment) use ($resource) {
                if ($resource->isExpiring()) {
                    $hexColor = '#f9c336';
                }

                if ($resource->isExpired()) {
                    $hexColor = '#e43b3b';
                }

                $attachment
                    ->color($hexColor)
                    ->block(function ($block) use ($resource) {
                        $block
                            ->type('section')
                            ->text([
                                'type' => 'mrkdwn',
                                'text' => sprintf(
                                    '*<%s|%s>*: *%s*',
                                    $resource->url,
                                    ucwords($resource->name),
                                    $resource->days_til_expired,
                                ),
                            ]);
                    })
                    ->block(function ($block) use ($resource) {
                        $block
                            ->type('context')
                            ->elements([
                                [
                                    'type' => 'mrkdwn',
                                    'text' => sprintf(
                                        "Used in %s modules\t\t\tExpiration Date: %s",
                                        $resource->modules->count(),
                                        $resource->expiration_date->format('m/d/Y'),
                                    ),
                                ],
                            ]);
                    })
                    ->dividerBlock();
            });
        });

        return $msg;
    }
}