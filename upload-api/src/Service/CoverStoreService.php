<?php
/**
 * @file
 * Service to handle cover store.
 */
namespace App\Service;

use App\Entity\Cover;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class CoverStoreService
 */
class CoverStoreService {

    private $removePath;
    private $client;
    private $storage;
    private $filesystem;

    /**
     * CoverStoreService constructor.
     *
     * @param HttpClientInterface $httpClient
     * @param StorageInterface $storage
     * @param Filesystem $filesystem
     */
    public function __construct(HttpClientInterface $httpClient, StorageInterface $storage, Filesystem $filesystem)
    {
        $this->client = $httpClient;
        $this->storage = $storage;
        $this->filesystem = $filesystem;

        // @TODO: Move into config.
        $this->removePath = 'https://res.cloudinary.com/itkdev/image/upload/v1587382972/UploadService/';
    }

    /**
     * Check if the file exists at the cover store.
     *
     * @param Cover $cover
     *   The cover entity to check
     *
     * @return bool
     *   True if it exists remotely else false
     *
     * @throws TransportExceptionInterface
     */
    public function exists(Cover $cover): bool
    {
        $indexExists = $this->client->request('HEAD', $this->removePath.$cover->getFilePath())->getStatusCode();
        if (200 !== $indexExists) {
            return false;
        }
        return true;
    }

    /**
     * Create URL that matches cover store.
     *
     * @param Cover $cover
     *   The cover entity to generate url for
     *
     * @return string
     *   The remote url for the cover
     */
    public function generateUrl(Cover $cover): string
    {
        return $this->removePath.$cover->getFilePath();
    }

    /**
     * Remove the local file.
     *
     * @param $cover
     *   The cover to remove the file for.
     */
    public function removeLocalFile($cover): void
    {
        $file = $this->storage->resolvePath($cover, 'file');
        if ($this->filesystem->exists($file)) {
            $this->filesystem->remove($file);
        }
    }
}
