<?php

namespace Esc\Notification\Link;

abstract class NotificationLink implements LinkableInterface
{

    /**
     * @return string
     */
    abstract public function getNotificationMethodName(): string;

    /**
     * @var string
     */
    private $link;

    /**
     * NotificationLink constructor.
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }
}