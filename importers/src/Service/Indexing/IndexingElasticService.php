<?php

namespace App\Service\Indexing;

use App\Exception\SearchIndexException;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class IndexingElasticService implements IndexingServiceInterface
{
    private string $newIndexName;
    private string $indexAliasName;

    private Client $client;

    public function __construct(string $bindIndexingAlias, Client $client)
    {
        $this->indexAliasName = $bindIndexingAlias;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function add(IndexItem $item): void
    {
        /** @var IndexItem $item */
        $params = [
            'index' => $this->indexAliasName,
            'id' => $item->getId(),
            'body' => $item->toArray(),
        ];

        try {
            $response = $this->client->index($params);
            $this->refreshIndex($this->indexAliasName);
        } catch (SearchIndexException|ClientResponseException|MissingParameterException|ServerResponseException $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }

        if (201 !== $response->getStatusCode()) {
            throw new SearchIndexException('Unable to add item to index', $response->getStatusCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(int $id): void
    {
        $params = [
            'index' => $this->indexAliasName,
            'id' => $id,
        ];

        try {
            $response = $this->client->delete($params);
            $this->refreshIndex($this->indexAliasName);
        } catch (SearchIndexException|ClientResponseException|MissingParameterException|ServerResponseException $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new SearchIndexException('Unable to remove item from index', $response->getStatusCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bulkAdd(array $items): void
    {
        if (!isset($this->newIndexName)) {
            $this->newIndexName = $this->indexAliasName.'_'.date('Y-m-d-His');
            $this->createIndex($this->newIndexName);
        }

        $params = [];
        foreach ($items as $item) {
            /* @var IndexItem $item */
            $params['body'][] = [
                'index' => [
                    '_index' => $this->newIndexName,
                    '_id' => $item->getId(),
                ],
            ];

            $params['body'][] = $item->toArray();
        }

        try {
            $this->client->bulk($params);
        } catch (ClientResponseException|ServerResponseException $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Switch new index with old by updating alias.
     *
     * @throws SearchIndexException
     */
    public function switchIndex(): void
    {
        $existingIndexName = $this->getCurrentActiveIndexName();
        $this->refreshIndex($this->newIndexName);

        try {
            $this->client->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $this->newIndexName,
                                'alias' => $this->indexAliasName,
                            ],
                        ],
                    ],
                ],
            ]);
            $this->client->indices()->delete(['index' => $existingIndexName]);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Refresh index to ensure data is searchable.
     *
     * @param string $indexName
     *   Name of the index to refresh
     *
     * @throws SearchIndexException
     */
    private function refreshIndex(string $indexName): void
    {
        try {
            $this->client->indices()->refresh(['index' => $indexName]);
        } catch (ClientResponseException|ServerResponseException $e) {
            throw new SearchIndexException('Unable to create new index', (int) $e->getCode(), $e);
        }
    }

    /**
     * Get the current active index name base on alias.
     *
     * @return string
     *   The name of the active index
     *
     * @throws SearchIndexException
     */
    private function getCurrentActiveIndexName(): string
    {
        try {
            $response = $this->client->indices()->getAlias(['name' => $this->indexAliasName]);
        } catch (ClientResponseException|ServerResponseException $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new SearchIndexException('Unable to get aliases', $response->getStatusCode());
        }

        $aliases = $response->asArray();
        $aliases = array_keys($aliases);

        return array_pop($aliases);
    }

    /**
     * Create new index.
     *
     * @param string $indexName
     *   Name of the index to create
     *
     * @throws SearchIndexException
     */
    private function createIndex(string $indexName): void
    {
        try {
            $response = $this->client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 5,
                        'number_of_replicas' => 0,
                    ],
                    'mappings' => [
                        'properties' => [
                            'isIdentifier' => [
                                'type' => 'keyword',
                            ],
                            'imageFormat' => [
                                'type' => 'keyword',
                            ],
                            'imageUrl' => [
                                'type' => 'text',
                            ],
                            'width' => [
                                'type' => 'integer',
                            ],
                            'isType' => [
                                'type' => 'keyword',
                            ],
                            'height' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new SearchIndexException('Unable to create new index', $response->getStatusCode());
        }
    }
}
