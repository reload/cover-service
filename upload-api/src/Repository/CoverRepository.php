<?php

namespace App\Repository;

use App\Entity\Cover;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CoverRepository.
 */
class CoverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cover::class);
    }

    /**
     * Find all covers that have not been marked as uploaded.
     *
     * @param int $limit
     *   Limit the number of records
     *
     * @return Query
     *   DQL query
     */
    public function getIsNotUploaded(int $limit = 0): Query
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.isUploaded = false')
            ->orderBy('c.updatedAt', 'ASC');

        if (0 !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Find all covers that do not have remote url defined.
     *
     * @param int $limit
     *   Limit the number of records
     *
     * @return Query
     *   DQL query
     */
    public function getNoRemoteUrl(int $limit = 0): Query
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.remoteUrl is null')
            ->orderBy('c.updatedAt', 'ASC');

        if (0 !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery();
    }
}
