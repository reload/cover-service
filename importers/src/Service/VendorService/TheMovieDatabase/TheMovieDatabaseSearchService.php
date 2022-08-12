<?php

/**
 * @file
 * Handle search at the data well to utilize hasCover relations.
 */

namespace App\Service\VendorService\TheMovieDatabase;

use App\Exception\DataWellVendorException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class SearchService.
 *
 * @TODO: Refactor this to only have one extendable service that searches in the data well.
 */
class TheMovieDatabaseSearchService
{
    private const SEARCH_LIMIT = 50;

    private readonly string $agency;
    private readonly string $profile;
    private string $searchURL;
    private string $password;
    private string $user;

    /**
     * TheMovieDatabaseSearchService constructor.
     *
     * @param ParameterBagInterface $params
     *   The parameter bag
     * @param HttpClientInterface $httpClient
     *   The http client
     */
    public function __construct(
        ParameterBagInterface $params,
        private readonly HttpClientInterface $httpClient
    ) {
        $this->agency = $params->get('datawell.vendor.agency');
        $this->profile = $params->get('datawell.vendor.profile');
    }

    /**
     * Set search url.
     *
     * @param string $searchURL
     *   The search url
     */
    public function setSearchUrl(string $searchURL): void
    {
        $this->searchURL = $searchURL;
    }

    /**
     * Set user name to access the datawell.
     *
     * @param string $user
     *   The user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * Set password for the datawell.
     *
     * @param string $password
     *   The password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Perform data well search for given ac source.
     *
     * @param string $query
     *   The query to send
     * @param int $offset
     *   Result offset
     *
     * @return array (array|bool|int)[]
     *
     * @throws DataWellVendorException
     * @throws \JsonException
     */
    public function search(string $query, int $offset = 1): array
    {
        // Validate that the service configuration have been set.
        if (empty($this->searchURL) || empty($this->user) || empty($this->password)) {
            throw new DataWellVendorException('Missing data well access configuration');
        }

        $pidArray = [];

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->searchURL,
                [
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
                ]
            );

            $content = $response->getContent();
            $jsonResponse = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (array_key_exists('searchResult', $jsonResponse['searchResponse']['result'])) {
                if ($jsonResponse['searchResponse']['result']['hitCount']['$']) {
                    $pidArray = $this->extractData($jsonResponse);
                }

                // It seems that the "more" in the search result is always "false".
                $more = true;
            } else {
                $more = false;
            }
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $exception) {
            throw new DataWellVendorException($exception->getMessage(), (int) $exception->getCode());
        }

        return [$pidArray, $more, $offset + self::SEARCH_LIMIT];
    }

    /**
     * Extract data from response.
     *
     * @param array $result
     *   Array of the json decoded data
     *
     *   Array of all pid => url pairs found in response
     */
    private function extractData(array $result): array
    {
        $data = [];

        foreach ($result['searchResponse']['result']['searchResult'] as $item) {
            foreach ($item['collection']['object'] as $object) {
                $pid = $object['identifier']['$'];
                $record = $object['record'];

                $title = array_key_exists('title', $record) ? $object['record']['title'][0]['$'] : null;
                $date = array_key_exists('date', $record) ? $object['record']['date'][0]['$'] : null;
                $description = array_key_exists('description', $record) ? $object['record']['description'] : null;
                $originalYear = $this->getOriginalYear(array_column($description ?? [], '$'));
                $creators = array_key_exists('creator', $record) ? $object['record']['creator'] : null;
                $director = $this->getDirector($creators ?? []);

                if ($title && $date) {
                    $data[$pid] = [];
                    $data[$pid]['title'] = $title;
                    $data[$pid]['date'] = $date;
                    $data[$pid]['originalYear'] = $originalYear;
                    $data[$pid]['director'] = $director;
                }
            }
        }

        return $data;
    }

    /**
     * Extract the original year from the descriptions.
     *
     * @param array $descriptions
     *   Search array of descriptions
     *
     * @return false|string The original year or null
     */
    private function getOriginalYear(array $descriptions): false|string
    {
        $matches = [];

        foreach ($descriptions as $description) {
            $descriptionMatches = [];
            $match = preg_match('/(\d{4})/u', (string) $description, $descriptionMatches);

            if ($match) {
                $matches = array_unique(array_merge($matches, $descriptionMatches));
            }
        }

        $upperYear = (int) date('Y') + 2;
        $confirmedMatches = [];

        foreach ($matches as $matchString) {
            $match = $match = (int) $matchString;

            if ($match > 1850 && $match < $upperYear) {
                $confirmedMatches[] = $matchString;
            }
        }

        if (1 === count($confirmedMatches)) {
            return $confirmedMatches[0];
        }

        return false;
    }

    /**
     * Extract the director from the creators.
     *
     * @param array $creators
     *   Search array of creators
     *
     *   The director or null
     */
    private function getDirector(array $creators): ?string
    {
        $directors = [];

        foreach ($creators as $creator) {
            if (isset($creator['@type']['$']) && 'dkdcplus:drt' === $creator['@type']['$']) {
                if (isset($creator['$'])) {
                    $directors[] = $creator['$'];
                }
            }
        }

        // @TODO: Can there be more directors?
        if (count($directors) > 0) {
            return $directors[0];
        }

        return null;
    }
}
