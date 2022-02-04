<?php
/**
 * @file
 * Service to handle cover store.
 */

namespace App\Service;

use App\Entity\Cover;
use App\Service\CoverStore\CoverStoreInterface;
use App\Utils\CoverStore\CoverStoreItem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class CoverStoreService.
 */
class CoverService
{
    private StorageInterface $storage;
    private Filesystem $filesystem;
    private CoverStoreInterface $coverStore;

    /**
     * CoverStoreService constructor.
     *
     * @param HttpClientInterface $httpClient
     * @param StorageInterface $storage
     * @param Filesystem $filesystem
     */
    public function __construct(StorageInterface $storage, Filesystem $filesystem, CoverStoreInterface $coverStore)
    {
        $this->storage = $storage;
        $this->filesystem = $filesystem;
        $this->coverStore = $coverStore;
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
     * Create URL that matches cover store.
     *
     * @param string $identifier
     *   The cover entity to generate url for
     *
     * @return string
     *   The remote url for the cover if found else the empty string
     */
    public function generateUrl(string $identifier): string
    {
        /** @var CoverStoreItem $item */
        $item = $this->coverStore->search($identifier);

        return !empty($item) ? $item->getUrl() : '';
    }

    /**
     * Remove the local file.
     *
     * @param $cover
     *   The cover to remove the file for
     */
    public function removeLocalFile($cover): void
    {
        $file = $this->storage->resolvePath($cover, 'file');
        if ($this->filesystem->exists($file)) {
            $this->filesystem->remove($file);
        }
    }
}
