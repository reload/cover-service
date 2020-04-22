<?php

namespace App\Repository;

use App\Entity\Cover;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CoverRepository
 */
class CoverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cover::class);
    }

    /**
     * Find all covers that have not been uploaded.
     *
     * @return CoverRepository[]
     *   Array of Cover entities.
     */
    public function getIsNotUploaded(): array
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.isUploaded = false')
            ->orderBy('c.updatedAt', 'ASC')
            ->getQuery();

        return $query->execute();
    }
}
