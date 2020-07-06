<?php


namespace Esc\Notification\Link;


use Esc\Notification\Entity\Notification;

class ExternalLink extends NotificationLink
{
    /**
     * @return string
     */
    public function getNotificationMethodName(): string
    {
        return 'setExternalLink';
    }
}