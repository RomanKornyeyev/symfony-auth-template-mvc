<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserToken::class);
    }

    public function invalidateActiveEmailChangeTokensForUser(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.used', ':used')
            ->where('t.user = :user')
            ->andWhere('t.type = :type')
            ->andWhere('t.used = false')
            ->setParameter('used', true)
            ->setParameter('user', $user)
            ->setParameter('type', UserToken::TYPE_EMAIL_CHANGE)
            ->getQuery()
            ->execute();
    }

    public function findValidToken(string $token, string $type): ?UserToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.type = :type')
            ->andWhere('t.used = false')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('type', $type)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveEmailChangeTokenForUser(User $user): ?UserToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.type = :type')
            ->andWhere('t.used = false')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('type', UserToken::TYPE_EMAIL_CHANGE)
            ->setParameter('now', new \DateTime())
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}