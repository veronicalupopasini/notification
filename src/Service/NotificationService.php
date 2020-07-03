<?php

namespace Esc\Notification\Service;

use Esc\Notification\Entity\Notification;
use Esc\Notification\LinkableInterface\LinkableInterface;
use Esc\Notification\Repository\NotificationRepository;
use Esc\Notification\ValueObjects\Notification\Status;
use Esc\Notification\ValueObjects\Notification\Title;
use Esc\Notification\ValueObjects\Notification\Username;
use Assert\AssertionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Esc\MercurePublisherTrait;
use JsonException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class NotificationService
{
    use MercurePublisherTrait;

    private $objectManager;
    private $notificationRepository;
    private $normalizer;
    private $mercureEnabled;
    private $link;

    public function __construct(
        EntityManagerInterface $manager,
        NotificationRepository $notificationRepository,
        LinkableInterface $link,
        Publisher $publisher,
        NormalizerInterface $normalizer,
        bool $mercureEnabled
    )
    {
        $this->objectManager = $manager;
        $this->notificationRepository = $notificationRepository;
        $this->publisher = $publisher;
        $this->normalizer = $normalizer;
        $this->mercureEnabled = $mercureEnabled;
        $this->link = $link;
    }

    /**
     * @param int $id
     * @param string $linkValue
     * @param string $message
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function success(int $id, string $linkValue, string $message = ''): void
    {
        $this->update($id, Notification::SUCCESS_STATE, $message, $linkValue);
    }

    /**
     * @param int $id
     * @param string $linkValue
     * @param string $message
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function error(int $id, string $linkValue, string $message = ''): void
    {
        $this->update($id, Notification::ERROR_STATE, $message, $linkValue);
    }

    /**
     * @param AttributeBag $data
     * @param string $linkValue
     * @return int
     * @throws AssertionFailedException
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function create(AttributeBag $data, string $linkValue): int
    {
        $notification = new Notification();

        $username = $data->get('username');
        $subTitle = $data->get('subTitle');
        $title = $data->get('title');
        /*$link = $data->get('link');
        $externalLink = $data->get('externalLink');
        $apiLink = $data->get('apiLink');*/
        $status = $data->get('status');

        $notification->setUsername(new Username($username));
        $notification->setTitle(new Title($title));
        $notification->setSubTitle($subTitle);
        $notification->setStatus(new Status($status));

        $this->updateLink($this->link, $linkValue);

        /*$notification->setLink($link);
        $notification->setExternalLink($externalLink);
        $notification->setApiLink($apiLink);*/

        $this->objectManager->persist($notification);
        $this->objectManager->flush();

        if ($this->mercureEnabled) {
            $this->publish(
                'createNotification',
                json_encode($this->normalizer->normalize($notification, 'json'), JSON_THROW_ON_ERROR, 512)
            );
        }

        return $notification->getId();
    }

    /**
     * @param int $id
     * @param string $status
     * @param string $message
     * @param string $linkValue
     * @throws ExceptionInterface
     * @throws JsonException
     */
    private function update(int $id, string $status, string $message, string $linkValue): void
    {
        /** @var  $notification */
        $notification = $this->notificationRepository->getOneById($id);

        $this->updateLink($this->link, $linkValue);
        $notification->setStatus($status);
        $notification->setMessage($message);

        $this->objectManager->persist($notification);
        $this->objectManager->flush();

        if ($this->mercureEnabled) {
            $this->publish(
                'updateNotification',
                json_encode($this->normalizer->normalize($notification, 'json'), JSON_THROW_ON_ERROR, 512)
            );
        }
    }

    /**
     * @param LinkableInterface $link
     * @param string $linkValue
     * @return Notification
     */
    public function updateLink(LinkableInterface $link, string $linkValue)
    {
        if (!$link instanceof LinkableInterface) {
            throw new \RuntimeException('Invalid link');
        }
        return $link->syncLink($linkValue);
    }
}
