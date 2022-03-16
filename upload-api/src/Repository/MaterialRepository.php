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
     *
     * @return Query
     *   The query build
     */
    public function getByAgencyId(int $agencyId): Query
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.agencyId = :agencyId')
            ->setParameter(':agencyId', $agencyId);

        return $queryBuilder->getQuery();
    }
}
