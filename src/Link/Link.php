<?php


namespace Esc\Notification\Link;

class Link extends NotificationLink
{
    /**
     * @return string
     */
    public function getNotificationMethodName(): string
    {
        return 'setLink';
    }
}