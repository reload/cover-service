<?php

/**
 * @file
 * Handle search at the data well to utilize hasCover relations.
 */

namespace App\Service\DataWell;

use App\Exception\DataWellVendorException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class DataWellClient.
 */
class DataWellClient
{
    final public const SEARCH_LIMIT = 50;

    /**
     * DataWellSearchService constructor.
     *
     * @param string $agency
     * @param string $profile
     * @param string $searchURL
     * @param string $password
     * @param string $user
     * @param HttpClientInterface $httpClient
     */
    public function __construct(
        private readonly string $agency,
        private readonly string $profile,
        private readonly string $searchURL,
        private readonly string $password,
        private readonly string $user,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Perform data well search for given ac source.
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     *     List of "response content", "more results", "offset"
     *
     * @throws DataWellVendorException
     */
    public function search(string $query, int $offset = 0): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->searchURL, [
                'body' => '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:open="http://oss.dbc.dk/ns/opensearch">
                <soapenv:Header/>
                <soapenv:Body>
                <open:searchRequest>
                   <open:query>'.$query.'</open:query>
                   <open:agency>'.$this->agency.'</open:agency>
                   <open:profile>'.$this->profile.'</open:profile>
                   <open:allObjects>0</open:allObjects>
                   <open:authentication>
                      <open:groupIdAut>'.$this->agency.'</open:groupIdAut>
                      <open:passwordAut>'.$this->password.'</open:passwordAut>
                      <open:userIdAut>'.$this->user.'</open:userIdAut>
                   </open:authentication>
                   <open:objectFormat>dkabm</open:objectFormat>
                   <open:start>'.$offset.'</open:start>
                   <open:stepValue>'.self::SEARCH_LIMIT.'</open:stepValue>
                   <open:allRelations>1</open:allRelations>
                <open:relationData>uri</open:relationData>
                <outputType>json</outputType>
                </open:searchRequest>
                </soapenv:Body>
                </soapenv:Envelope>',
            ]);

            $content = $response->getContent();
            $jsonContent = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            throw new DataWellVendorException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        $more = $this->hasMoreResults($jsonContent);

        return [$jsonContent, $more, $offset + self::SEARCH_LIMIT];
    }

    /**
     * Extract isbn from result object.
     *
     * @param object $datawellObject
     *
     * @return string|null
     */
    public function extractIsbn(object $datawellObject): ?string
    {
        foreach ($datawellObject->record?->identifier ?? [] as $identifier) {
            if (property_exists($identifier, '@type')) {
                if ('dkdcplus:ISBN' === $identifier->{'@type'}->{'$'}) {
                    return $identifier->{'$'};
                }
            }
        }

        return null;
    }

    /**
     * Extract PIDs and matching cover urls from result set.
     *
     * @param object $jsonContent
     *   Array of the json decoded data
     * @param string $coverUrlRelationKey
     *   The datawell relation key that holds the cover URL
     *
     * @return array<string, ?string>
     *   Array of all pid => url pairs found in response
     */
    public function extractCoverUrl(object $jsonContent, string $coverUrlRelationKey): array
    {
        $data = [];

        if (isset($jsonContent->searchResponse?->result?->searchResult)) {
            foreach ($jsonContent->searchResponse->result->searchResult as $searchResult) {
                foreach ($searchResult->collection?->object ?? [] as $object) {
                    $pid = $object->identifier?->{'$'};
                    if (null !== $pid) {
                        $data[$pid] = null;
                        foreach ($object->relations?->relation ?? [] as $relation) {
                            if ($coverUrlRelationKey === $relation->relationType?->{'$'}) {
                                $coverUrl = $relation->relationUri?->{'$'};
                                $data[$pid] = (string) $coverUrl;
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Extract PIDs and matching objects from result set.
     *
     * @param object $jsonContent
     *   Array of the json decoded data
     *
     * @return array<string, object>
     *   Array of all pid => object pairs found in response
     */
    public function extractData(object $jsonContent): array
    {
        $data = [];

        if (isset($jsonContent->searchResponse?->result?->searchResult)) {
            foreach ($jsonContent->searchResponse?->result?->searchResult ?? [] as $searchResult) {
                foreach ($searchResult->collection?->object ?? [] as $object) {
                    $pid = $object->identifier?->{'$'};
                    if (null !== $pid) {
                        $data[$pid] = $object;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Has the search more results.
     *
     * @param object $jsonContent
     *   Json decode result
     *
     * @return bool
     *
     * @throws DataWellVendorException
     */
    private function hasMoreResults(object $jsonContent): bool
    {
        $more = $jsonContent->searchResponse?->result?->more->{'$'};

        return match ($more) {
            'true' => true,
            'false' => false,
            default => throw new DataWellVendorException('Datawell returned unknown value for "more": '.var_export($more, true))
        };
    }
}
