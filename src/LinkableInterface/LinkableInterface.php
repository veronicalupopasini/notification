<?php


namespace Esc\Notification\LinkableInterface;


use Esc\Notification\Entity\Notification;

interface LinkableInterface
{
    public function syncLink(string $linkToUpdate): Notification;
}