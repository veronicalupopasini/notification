<?php


namespace Esc\Notification\LinkableInterface;


use Esc\Notification\Entity\Notification;

class Link implements LinkableInterface
{
    /**
     * @var Notification
     */
    private $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function syncLink($linkToUpdate): Notification
    {
        $this->notification->setLink($linkToUpdate);
        return $this->notification;
    }
}