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
     * @return Query
     *   DQL query
     */
    public function getIsNotUploaded(): Query
    {
        return $this->createQueryBuilder('c')
            ->where('c.isUploaded = false')
            ->orderBy('c.updatedAt', 'ASC')
            ->getQuery();
    }
}
