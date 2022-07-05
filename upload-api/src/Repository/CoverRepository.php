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
     * Get query for all covers that have not been marked as uploaded.
     *
     * @param int $limit
     *   Limit the number of records
     *
     * @return Query
     *   DQL query
     */
    public function getIsNotUploadedQuery(int $limit = 0): Query
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
     * Get query for all covers that do not have remote url defined.
     *
     * @param int $limit
     *   Limit the number of records
     *
     * @return Query
     *   DQL query
     */
    public function getNoRemoteUrlQuery(int $limit = 0): Query
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.remoteUrl is null')
            ->orderBy('c.updatedAt', 'ASC');

        if (0 !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Get query for all covers that are nor related to a Material.
     *
     * @param int $limit
     *   Limit for query
     * @param int $offset
     *   Offset for query
     *
     * @return Query
     *   DQL Query
     */
    public function getHasNoMaterialQuery(int $limit = 0, int $offset = 0): Query
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->leftJoin('c.material', 'm')
            ->where('m.cover IS NULL');

        if (0 !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if (0 !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery();
    }
}
