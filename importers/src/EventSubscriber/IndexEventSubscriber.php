<?php

/**
 * @file
 * Index event subscriber that updates the "search" database table thereby ensuring that the material gets indexed into
 * the search engine.
 */

namespace App\EventSubscriber;

use App\Entity\Image;
use App\Entity\Search;
use App\Entity\Source;
use App\Event\IndexReadyEvent;
use App\Utils\OpenPlatform\Material;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class IndexEventSubscriber.
 */
class IndexEventSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    /**
     * IndexEventSubscriber constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $informationLogger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $informationLogger)
    {
        $this->em = $entityManager;
        $this->logger = $informationLogger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            IndexReadyEvent::NAME => 'onIndexEvent',
        ];
    }

    /**
     * Updated event handler.
     *
     * @param IndexReadyEvent $event
     *
     * @return void
     */
    public function onIndexEvent(IndexReadyEvent $event): void
    {
        try {
            $material = $event->getMaterial();
            $image = $this->getImage($event->getImageId());
            $source = (null !== $image) ? $image->getSource() : null;

            // If image or source are null something is broken in the data,
            // so we can't proceed
            if (null === $image || null === $source) {
                $this->logger->error('Index Ready Event Error', [
                    'service' => 'IndexEventSubscriber',
                    'message' => 'Image and/or Source are null for material',
                    'identifiers' => $material->getIdentifiers(),
                ]);

                return;
            }

            $searchRepos = $this->em->getRepository(Search::class);

            // There may exist a race condition when multiple queues are
            // running. To ensure we don't insert duplicates we need to
            // wrap our search/update/insert in a transaction.
            $this->em->getConnection()->beginTransaction();

            try {
                foreach ($material->getIdentifiers() as $identifier) {
                    /* @var Search $search */
                    $search = $searchRepos->findOneBy([
                        'isIdentifier' => $identifier->getId(),
                        'isType' => $identifier->getType(),
                    ]);

                    if (empty($search)) {
                        // It did not exist, so create new record. Which will automatically update the search indexes
                        // on flush.
                        $search = new Search();
                        $search->setIsType($identifier->getType())
                            ->setIsIdentifier($identifier->getId())
                            ->setImageUrl($image->getCoverStoreURL())
                            ->setImageFormat($image->getImageFormat())
                            ->setWidth($image->getWidth())
                            ->setHeight($image->getHeight())
                            ->setCollection($material->isCollection())
                            ->setSource($source);

                        $this->em->persist($search);
                    } else {
                        if ($this->shouldOverride($material, $source, $search)) {
                            $search->setImageUrl($image->getCoverStoreURL())
                                ->setImageFormat($image->getImageFormat())
                                ->setWidth($image->getWidth())
                                ->setHeight($image->getHeight())
                                ->setCollection($material->isCollection())
                                ->setSource($source);
                        }
                    }
                }

                // Make every thing stick.
                $this->em->flush();
                $this->em->getConnection()->commit();
            } catch (\Exception $exception) {
                $this->em->getConnection()->rollBack();
                $this->logger->error('Database exception: '.get_class($exception), [
                    'service' => 'IndexEventSubscriber',
                    'message' => $exception->getMessage(),
                    'identifiers' => $material->getIdentifiers(),
                ]);
            }

            // Set the lasted indexed out side the transaction, so it will always be set even at search entity errors.
            $source->setLastIndexed(new \DateTime());
            $this->em->flush();
        } catch (ConnectionException $exception) {
            $this->logger->error('Database Connection Exception', [
                'service' => 'IndexEventSubscriber',
                'message' => $exception->getMessage(),
                'identifiers' => $material->getIdentifiers(),
            ]);
        } catch (\Exception $exception) {
            $this->logger->error('Index Exception', [
                'service' => 'IndexEventSubscriber',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Determine if the search record should be overridden.
     *
     * @param Material $material
     *   Material from search result
     * @param source $source
     *   Source entity used for raking
     * @param search $search
     *  Search entity used for raking
     *
     * @return bool
     */
    private function shouldOverride(Material $material, Source $source, Search $search): bool
    {
        // Rank is unique so can never be identical for two different vendors
        // but we need to update search if update image from same vendor.
        $sourceRank = $source->getVendor()->getRank();
        $searchRank = $search->getSource()->getVendor()->getRank();
        if ($sourceRank <= $searchRank) {
            // Collection should not override covers on items that already have unique cover.
            if ($material->isCollection()) {
                // Unless it is marked as a collection search entity then this may be an image update.
                if ($search->isCollection()) {
                    return true;
                }

                return false;
            }

            // Not a collection and rank is higher, so allow override.
            return true;
        }

        return false;
    }

    /**
     * Find image entity in the database.
     *
     * @param int $imageId
     *   Database ID for the image
     *
     * @return Image|null
     *   Image entity if found else null
     */
    private function getImage(int $imageId): ?Image
    {
        $repos = $this->em->getRepository(Image::class);

        return $repos->findOneById($imageId);
    }
}
