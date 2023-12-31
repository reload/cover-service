<?php

/**
 * @file
 * Interface for handling Cover storing.
 */

namespace App\Service\CoverStore;

use App\Utils\CoverStore\CoverStoreItem;

/**
 * Interface CoverStoreInterface.
 */
interface CoverStoreInterface
{
    /**
     * Search in the cover store.
     *
     * @param string $identifier
     *   Identifier to search for in user upload
     * @param bool $refresh
     *   By-pass search cache
     *
     * @return CoverStoreItem[]
     *   Array with the found items or empty if non found
     */
    public function search(string $identifier, bool $refresh = false): array;
}
