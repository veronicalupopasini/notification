<?php


namespace Esc\Notification\Link;


use Esc\Notification\Entity\Notification;

class ApiLink extends NotificationLink
{
    /**
     * @return string
     */
    public function getNotificationMethodName(): string
    {
        return 'setApiLink';
    }
}