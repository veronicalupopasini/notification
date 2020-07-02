<?php

namespace Esc\Notification\Repository;

use Esc\Notification\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\QueryException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\Security\Core\Security;
use Esc\Repository\Repository as EscRepository;

/**
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends EscRepository
{
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Notification::class);
        $this->security = $security;
    }

    public function findOneById(int $id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param int $id
     * @return Notification
     * @throws RuntimeException;
     */
    public function getOneById(int $id): Notification
    {
        $row = $this->findOneById($id);

        if (!$row) {
            throw new RuntimeException(sprintf('Notification %s not found', $id));
        }

        return $row;
    }

    /**
     * @param AttributeBag $parameters
     * @return array
     * @throws QueryException
     */
    public function findByCriteria(AttributeBag $parameters): array
    {
        return $this->createQueryBuilder('n')
            ->select('n')
            ->addCriteria($this->getPaginatedAndFilteredCriteria($parameters))
            ->getQuery()
            ->getArrayResult();
    }

    public function countByCriteria(array $filters): int
    {
        return count($this->matching($this->getFiltersCriteria($filters)));
    }

    private function getFiltersCriteria(array $filters): Criteria
    {
        $filtersBag = $this->prepareFiltersCriteria($filters);

        $criteria = Criteria::create();

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $user = $this->security->getUser();
            if ($user !== null) {
                $criteria->andWhere(Criteria::expr()->eq('username', $user->getUsername()));
            }
        }
        if ($filtersBag->has('daily')) {
            $criteria->andWhere(Criteria::expr()->gt('created', (new \DateTime())->modify('-1 day')));
        }

        return $criteria;
    }

    private function getPaginatedAndFilteredCriteria(AttributeBag $parameters): Criteria
    {
        return $this->getFiltersCriteria($parameters->get('filters'))
            ->orderBy($parameters->get('sortBy'))
            ->setMaxResults($parameters->get('limit'))
            ->setFirstResult($parameters->get('offset'));
    }

    private function prepareFiltersCriteria(array $filters): AttributeBag
    {
        $filtersBag = new AttributeBag();
        $filtersBag->initialize($filters);

        return $filtersBag;
    }
}
