<?php

namespace App\Service\Indexing;

use App\Exception\SearchIndexException;
use OpenSearch\Client;
use OpenSearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class IndexingElasticService implements IndexingServiceInterface
{
    private ?string $newIndexName = null;

    public function __construct(
        private readonly string $indexAliasName,
        private readonly Client $client,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function index(IndexItem $item): void
    {
        /** @var IndexItem $item */
        $params = [
            'index' => $this->indexAliasName,
            'id' => $item->getId(),
            'body' => $item->toArray(),
        ];

        try {
            $this->client->index($params);

        } catch (Throwable $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): void
    {
        $params = [
            'index' => $this->indexAliasName,
            'id' => $id,
        ];

        try {
            $this->client->delete($params);

        } catch (Throwable $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function bulk(array $items): void
    {
        try {
            if (null === $this->newIndexName) {
                $this->newIndexName = $this->indexAliasName.'_'.date('Y-m-d-His');
                $this->createEsIndex($this->newIndexName);
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

            $this->client->bulk($params);
        } catch (Throwable $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createIndex(): void
    {
        if ($this->indexExists()) {
            throw new SearchIndexException('Index already exists');
        }

        $newIndexName = $this->indexAliasName.'_'.date('Y-m-d-His');
        $this->createEsIndex($newIndexName);
        $this->refreshIndex($newIndexName);

        try {
            $this->client->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $newIndexName,
                                'alias' => $this->indexAliasName,
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            throw new SearchIndexException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function indexExists(): bool
    {
        try {
            $this->client->indices()->getAlias(['name' => $this->indexAliasName]);
        } catch (Missing404Exception) {
            return false;
        }

        return true;
    }

    /**
     * Switch new index with old by updating alias.
     *
     * @throws SearchIndexException
     */
    public function switchIndex(): void
    {
        if (null === $this->newIndexName) {
            throw new SearchIndexException('New index name cannot be null');
        }

        try {
            $existingIndexName = $this->getCurrentActiveIndexName();
            $this->refreshIndex($this->newIndexName);

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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            throw new SearchIndexException('Unable to refresh index', (int) $e->getCode(), $e);
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
            $aliases = array_keys($response);
            return array_pop($aliases);
        } catch (Throwable $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Create new index.
     *
     * Index optimizations
     *
     * @see https://www.inventaconsulting.net/post/a-guide-to-optimizing-elasticsearch-mappings
     *
     * 'dynamic' => 'strict'
     *
     * If new fields are detected, an exception is thrown and the document is rejected.
     * New fields must be explicitly added to the mapping.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic.html#dynamic-parameters
     *
     * 'index_options' => 'docs'
     *
     * The index_options parameter controls what information is added to the
     * inverted index for search and highlighting purposes.
     * 'docs': Only the doc number is indexed. Can answer the question
     * Does this term exist in this field?
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/8.5/index-options.html
     *
     * 'doc_values' => false
     *
     * If you are sure that you don’t need to sort or aggregate on a field, or access the
     * field value from a script, you can disable doc values in order to save disk space
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/8.5/doc-values.html#_disabling_doc_values
     *
     * 'norms' => false
     *
     * Although useful for scoring, norms also require quite a lot of disk (typically in the
     * order of one byte per document per field in your index, even for documents that don’t
     * have this specific field). As a consequence, if you don’t need scoring on a specific
     * field, you should disable norms on that field.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/8.5/norms.html
     *
     * @param string $indexName
     *   Name of the index to create
     *
     * @throws SearchIndexException
     */
    private function createEsIndex(string $indexName): void
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
                        'dynamic' => 'strict',
                        'properties' => [
                            'isType' => [
                                'type' => 'keyword',
                                'index_options' => 'docs',
                                'doc_values' => false,
                                'norms' => false,
                            ],
                            'isIdentifier' => [
                                'type' => 'keyword',
                                'index_options' => 'docs',
                                // API responses are sorted by identifier
                                'doc_values' => true,
                                'norms' => false,
                            ],
                            'imageFormat' => [
                                'type' => 'keyword',
                                'index_options' => 'docs',
                                'index' => false,
                                'doc_values' => false,
                                'norms' => false,
                            ],
                            'imageUrl' => [
                                'type' => 'text',
                                'index' => false,
                                'norms' => false,
                            ],
                            'width' => [
                                'type' => 'integer',
                                'index' => false,
                                'doc_values' => false,
                            ],
                            'height' => [
                                'type' => 'integer',
                                'doc_values' => false,
                            ],
                            'generic' => [
                                'type' => 'boolean',
                                'doc_values' => false,
                            ],
                        ],
                    ],
                ],
            ]);

            if (empty($response['acknowledged'])) {
                throw new SearchIndexException('Unable to create new index');
            }

        } catch (Throwable $e) {
            throw new SearchIndexException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
