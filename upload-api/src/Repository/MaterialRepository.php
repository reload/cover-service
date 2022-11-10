<?php

namespace App\Repository;

use App\Entity\Material;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CoverRepository.
 */
class MaterialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Material::class);
    }

    /**
     * Get query to load materials based on agency id.
     *
     * @param int $agencyId
     *   Agency ID
     * @param bool $isNotUploaded
     *   Filter base on those that do not have covers marked as uploaded
     * @param int $limit
     *   Limit the number of records
     * @param int $offset
     *   The database offset to start at
     *
     * @return Query
     *   The query build
     */
    public function getByAgencyId(int $agencyId, bool $isNotUploaded, int $limit = 0, int $offset = 0): Query
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.agencyId = :agencyId')
            ->setParameter(':agencyId', $agencyId);

        if ($isNotUploaded) {
            $queryBuilder->join('m.cover', 'c')
                ->where('c.isUploaded = 0');
        }

        if (0 !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if (0 !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Get all materials.
     *
     * @param bool $isNotUploaded
     *   Filter base on those that do not have covers marked as uploaded
     * @param int $limit
     *   Limit the number of records
     * @param int $offset
     *   The database offset to start at
     *
     * @return Query
     *   The query build
     */
    public function getAll(bool $isNotUploaded, int $limit = 0, int $offset = 0): Query
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($isNotUploaded) {
            $queryBuilder->join('m.cover', 'c')
                ->where('c.isUploaded = 0');
        }

        if (0 !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if (0 !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery();
    }
}
