<?php


namespace Esc\Notification\Link;

interface LinkableInterface
{
    /**
     * @return string
     */
    public function getLink(): string;
}