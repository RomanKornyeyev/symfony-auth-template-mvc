<?php

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    /**
     * Busca una relación entre dos usuarios en cualquier dirección.
     */
    public function findBetween(User $userA, User $userB): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.requester = :a AND f.receiver = :b)')
            ->orWhere('(f.requester = :b AND f.receiver = :a)')
            ->setParameter('a', $userA)
            ->setParameter('b', $userB)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Devuelve los amigos aceptados de un usuario.
     */
    public function findAcceptedFriends(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('(f.requester = :user OR f.receiver = :user)')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve las solicitudes pendientes recibidas por un usuario.
     */
    public function findPendingReceivedBy(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.receiver = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve las solicitudes de amistad enviadas por el usuario que siguen pendientes.
     */
    public function findPendingSentBy(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.requester = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve las relaciones donde el usuario ha bloqueado a otro (él es el requester).
     */
    public function findBlockedBy(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.requester = :user')
            ->andWhere('f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_BLOCKED)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Comprueba si existe un bloqueo entre dos usuarios (en cualquier dirección).
     */
    public function isBlocked(User $userA, User $userB): bool
    {
        $qb = $this->createQueryBuilder('f');

        $result = $qb
            ->select('COUNT(f.id)')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX('f.requester = :a', 'f.receiver = :b'),
                    $qb->expr()->andX('f.requester = :b', 'f.receiver = :a')
                )
            )
            ->andWhere('f.status = :status')
            ->setParameter('a', $userA)
            ->setParameter('b', $userB)
            ->setParameter('status', Friendship::STATUS_BLOCKED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
