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
     *
     * @return Query
     *   The query build
     */
    public function getByAgencyId(int $agencyId, bool $isNotUploaded): Query
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.agencyId = :agencyId')
            ->setParameter(':agencyId', $agencyId);

        if ($isNotUploaded) {
            $queryBuilder->join('m.cover', 'c')
                ->where('c.isUploaded = 0');
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Get all materials.
     *
     * @param bool $isNotUploaded
     *   Filter base on those that do not have covers marked as uploaded
     *
     * @return Query
     *   The query build
     */
    public function getAll(bool $isNotUploaded): Query
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($isNotUploaded) {
            $queryBuilder->join('m.cover', 'c')
                ->where('c.isUploaded = 0');
        }

        return $queryBuilder->getQuery();
    }
}
