<?php

/**
 * @file
 * Interface for handling Cover storing.
 */

namespace App\Service\CoverStore;

use App\Exception\CoverStoreAlreadyExistsException;
use App\Exception\CoverStoreCredentialException;
use App\Exception\CoverStoreException;
use App\Exception\CoverStoreNotFoundException;
use App\Exception\CoverStoreTooLargeFileException;
use App\Exception\CoverStoreUnexpectedException;
use App\Utils\CoverStore\CoverStoreItem;

/**
 * Interface CoverStoreInterface.
 */
interface CoverStoreInterface
{
    /**
     * Upload image the store.
     *
     * @param string $url
     *   The URL to fetch the image from
     * @param string $folder
     *   The vendor that supplied the image used to organize the images
     * @param string $identifier
     *   The name that the file should be saved under
     * @param array $tags
     *   Tags to enrich the image in the store
     *
     * @return coverStoreItem
     *   CoverStoreItem object contain information about the image
     *
     * @throws CoverStoreCredentialException
     * @throws CoverStoreException
     * @throws CoverStoreNotFoundException
     * @throws CoverStoreTooLargeFileException
     * @throws CoverStoreUnexpectedException
     */
    public function upload(string $url, string $folder, string $identifier, array $tags = []): CoverStoreItem;

    /**
     * Remove cover from the store.
     *
     * On error exception is thrown.
     *
     * @param string $folder
     *   The folder to place the cover in
     * @param string $identifier
     *   Filename for the cover in the store
     *
     * @throws CoverStoreAlreadyExistsException
     * @throws CoverStoreCredentialException
     * @throws CoverStoreNotFoundException
     * @throws CoverStoreTooLargeFileException
     * @throws CoverStoreUnexpectedException
     * @throws CoverStoreException
     */
    public function remove(string $folder, string $identifier): void;

    /**
     * Search in the cover store.
     *
     * @param string $query
     *   The search query to execute
     * @param string|null $folder
     *   The folder (vendor) to search in or null to search the full cover store
     * @param int|null $maxResults
     *   The maximum number of results to return. Omit to get all results.
     *
     * @return iterable<CoverStoreItem>
     *   Iterable with the found items or empty if non found
     *
     * @throws CoverStoreException
     */
    public function search(string $query, string $folder = null, int $maxResults = null): iterable;

    /**
     * Search in the cover store.
     *
     * @param string $folder
     *   The folder to get items from
     * @param int|null $maxResults
     *   The maximum number of results to return. Omit to get all results.
     *
     * @return iterable<CoverStoreItem>
     *   Iterable with the found items or empty if non found
     *
     * @throws CoverStoreException
     */
    public function getFolder(string $folder, int $maxResults = null): iterable;

    /**
     * Mover item in the cover store.
     *
     * @param string $source
     *   The source cover to move
     * @param string $destination
     *   The destination to move the cover into
     * @param bool $overwrite
     *   Should the source overwritten
     *
     * @return CoverStoreItem
     *   The cover information after it have been moved
     *
     * @throws CoverStoreException
     */
    public function move(string $source, string $destination, bool $overwrite = false): CoverStoreItem;
}
