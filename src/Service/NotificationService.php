<?php

namespace Esc\Notification\Service;

use Assert\Assertion;
use Esc\Notification\Entity\Notification;
use Esc\Notification\Link\NotificationLink;
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

    public function __construct(
        EntityManagerInterface $manager,
        NotificationRepository $notificationRepository,
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
    }

    /**
     * @param int $id
     * @param string $message
     * @param NotificationLink $notificationLink
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function success(int $id, string $message = '', ?NotificationLink $notificationLink = null): void
    {
        $this->update($id, Notification::SUCCESS_STATE, $message, $notificationLink);
    }

    /**
     * @param int $id
     * @param string $message
     * @param NotificationLink $notificationLink
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function error(int $id, string $message = '', ?NotificationLink $notificationLink = null): void
    {
        $this->update($id, Notification::ERROR_STATE, $message, $notificationLink);
    }

    /**
     * @param AttributeBag $data
     * @return int
     * @throws AssertionFailedException
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function create(AttributeBag $data): int
    {
        $notification = new Notification();

        $username = $data->get('username');
        $subTitle = $data->get('subTitle');
        $title = $data->get('title');
        $status = $data->get('status');
        $linkValue = $data->get('link');
        if ($linkValue){
            Assertion::isInstanceOf($linkValue, NotificationLink::class);
            $methodName = $linkValue->getNotificationMethodName();
            $notification->$methodName($linkValue->getLink());
        }

        $notification->setUsername(new Username($username));
        $notification->setTitle(new Title($title));
        $notification->setSubTitle($subTitle);
        $notification->setStatus(new Status($status));

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
     * @param NotificationLink $linkValue
     * @throws ExceptionInterface
     * @throws JsonException
     */
    private function update(int $id, string $status, string $message, ?NotificationLink $linkValue = null): void
    {
        /** @var  $notification */
        $notification = $this->notificationRepository->getOneById($id);

        if ($linkValue){
            $methodName = $linkValue->getNotificationMethodName();
            $notification->$methodName($linkValue->getLink());
        }

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
}
