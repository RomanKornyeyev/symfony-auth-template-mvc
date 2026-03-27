<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Busca usuarios por nickname (parcial), excluyendo al usuario actual
     * y a los que tengan isSearchable = false.
     *
     * @return User[]
     */
    public function searchByNickname(string $q, User $exclude, int $limit = 20): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.settings', 's')
            ->where('u.nickname LIKE :q')
            ->andWhere('u.id != :exclude')
            ->andWhere('u.isVerified = true')
            ->andWhere('s.id IS NULL OR s.isSearchable = true')
            ->setParameter('q', '%' . $q . '%')
            ->setParameter('exclude', $exclude->getId())
            ->orderBy('u.nickname', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
