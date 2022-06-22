<?php

/**
 * @file
 * Handle image storing at Cloudinary.
 */

namespace App\Service\CoverStore;

use App\Exception\CoverStoreCredentialException;
use App\Utils\CoverStore\CoverStoreItem;
use Cloudinary\Api\Exception\GeneralError;
use Cloudinary\Api\Search\SearchApi;
use Cloudinary\Configuration\Configuration;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Class CloudinaryCoverStoreService.
 */
class CloudinaryCoverStoreService implements CoverStoreInterface
{
    private string $folder;
    private AdapterInterface $cache;
    private int $cacheTTL;

    /**
     * CloudinaryCoverStoreService constructor.
     *
     * @param string $bindCloudinaryCloudName
     *   The account cloud name
     * @param string $bindCloudinaryApiKey
     *   API key
     * @param string $bindCloudinaryApiSecret
     *   API secret
     *
     * @throws CoverStoreCredentialException
     */
    public function __construct(string $bindCloudinaryCloudName, string $bindCloudinaryApiKey, string $bindCloudinaryApiSecret, string $bindCloudinaryFolder, int $bindCloudinarySearchTTL, AdapterInterface $cache)
    {
        if (empty($bindCloudinaryCloudName)) {
            throw new CoverStoreCredentialException('Missing Cloudinary configuration in environment: CLOUDINARY_CLOUD_NAME');
        }
        if (empty($bindCloudinaryApiKey)) {
            throw new CoverStoreCredentialException('Missing Cloudinary configuration in environment: CLOUDINARY_API_KEY');
        }
        if (empty($bindCloudinaryApiSecret)) {
            throw new CoverStoreCredentialException('Missing Cloudinary configuration in environment: CLOUDINARY_API_SECRET');
        }

        $this->folder = $bindCloudinaryFolder;

        // Set global Cloudinary configuration.
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $bindCloudinaryCloudName,
                'api_key' => $bindCloudinaryApiKey,
                'api_secret' => $bindCloudinaryApiSecret,
                'secure' => true,
            ],
        ]);

        $this->cache = $cache;
        $this->cacheTTL = $bindCloudinarySearchTTL;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Cloudinary\Api\Exception\GeneralError
     */
    public function search(string $identifier = null, bool $refresh = false): array
    {
        try {
            // Try getting item from cache.
            $cacheItem = $this->cache->getItem('coverstore.search_query'.str_replace(':', '', $identifier));
        } catch (InvalidArgumentException $exception) {
            throw new GeneralError('Invalid cache argument');
        }

        // Check if cache should be used if item have been located.
        if ($refresh || !$cacheItem->isHit()) {
            $search = new SearchApi();
            $search->expression('folder='.$this->folder)
                ->sortBy('public_id', 'desc')
                ->maxResults(100);

            if (!is_null($identifier)) {
                $query = 'public_id:'.$this->folder.'/'.addcslashes($identifier, ':');
                $search->expression($query);
            }
            $result = $search->execute()->getArrayCopy();

            $items = [];
            foreach ($result['resources'] as $resources) {
                $item = new CoverStoreItem();
                $item->setId($resources['public_id'])
                    ->setUrl($resources['secure_url'])
                    ->setVendor($this->folder)
                    ->setSize($resources['bytes'])
                    ->setWidth((int) $resources['width'])
                    ->setHeight((int) $resources['height'])
                    ->setImageFormat($resources['format']);
                $items[] = $item;
            }

            $cacheItem->expiresAfter($this->cacheTTL);
            $cacheItem->set($items);
            $this->cache->save($cacheItem);
        } else {
            $items = $cacheItem->get();
        }

        return $items;
    }
}
