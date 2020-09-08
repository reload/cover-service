<?php
/**
 * @file
 * Doctrine query extension to filter base on current authenticated user.
 */

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Cover;
use App\Entity\Material;
use DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

/**
 * Class CurrentUserExtension.
 */
class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private $security;

    /**
     * CurrentUserExtension constructor.
     *
     * @param Security $security
     *   The security service with the current user
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    /**
     * {@inheritdoc}
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    /**
     * Helper function to add where clause to query statements.
     *
     * Filter base on current users agency id.
     *
     * @param QueryBuilder $queryBuilder
     *   The current query builder
     * @param string $resourceClass
     *   The entity resource class for the current query
     */
    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (in_array($resourceClass, [Material::class, Cover::class]) && null !== $user) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->andWhere(sprintf('%s.agencyId = :current_agency', $rootAlias));
            $queryBuilder->setParameter('current_agency', $user->getAgency());
        }
    }
}
