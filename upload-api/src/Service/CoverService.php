<?php
/**
 * @file
 * Service to handle cover store.
 */

namespace App\Service;

use App\Entity\Cover;
use App\Service\CoverStore\CoverStoreInterface;
use App\Utils\CoverStore\CoverStoreItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class CoverStoreService.
 */
class CoverService
{
    /**
     * CoverStoreService constructor.
     *
     * @param StorageInterface $storage
     * @param Filesystem $filesystem
     * @param CoverStoreInterface $coverStore
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly Filesystem $filesystem,
        private readonly CoverStoreInterface $coverStore,
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * Check if the file exists at the cover store.
     *
     * @param string $identifier
     *   The cover to checking for
     *
     * @return bool
     *   True if it exists remotely else false
     */
    public function exists(string $identifier): bool
    {
        return !empty($this->coverStore->search($identifier));
    }

    /**
     * Search the cover store.
     *
     * @param string $identifier
     *   Identifier to search for in the cover store
     *
     * @return CoverStoreItem
     */
    public function search(string $identifier): ?CoverStoreItem
    {
        $items = $this->coverStore->search($identifier);

        return reset($items);
    }

    /**
     * Create URL that matches cover store.
     *
     * @param Cover $cover
     *   The cover entity to generate url for
     *
     * @return string
     *   The remote url for the cover if found else the empty string
     */
    public function generateUrl(Cover $cover): string
    {
        $url = $cover->getRemoteUrl();
        if (is_null($url)) {
            /** @var CoverStoreItem $item */
            $items = $this->coverStore->search($cover->getMaterial()->getIsIdentifier());
            $url = !empty($items) ? reset($items)->getUrl() : '';

            // This is a side effect, but it's the best of all evils at the moment.
            $cover->setRemoteUrl($url);
            $this->em->flush();
        }

        return $url;
    }

    /**
     * Remove the local file.
     *
     * @param Cover $cover
     *   The cover to remove the file for
     */
    public function removeLocalFile(Cover $cover): void
    {
        $file = $this->storage->resolvePath($cover, 'file');
        if (null !== $file && $this->filesystem->exists($file)) {
            $this->filesystem->remove($file);
        }
    }

    /**
     * Check if file exits in the file system.
     *
     * @param cover $cover
     *   The cover entity to check file for
     *
     * @return bool
     *   If found ture else false
     */
    public function existsLocalFile(Cover $cover): bool
    {
        $file = $this->storage->resolvePath($cover, 'file');

        return null !== $file && $this->filesystem->exists($file);
    }
}
