<?php

/**
 * @file
 * Handle search at the data well to utilize hasCover relations.
 */

namespace App\Service\VendorService\TheMovieDatabase;

use App\Exception\DataWellVendorException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class SearchService.
 */
class TheMovieDatabaseSearchService
{
    private const SEARCH_LIMIT = 50;

    private $client;

    private $agency;
    private $profile;
    private $searchURL;
    private $password;
    private $user;

    /**
     * TheMovieDatabaseSearchService constructor.
     *
     * @param ParameterBagInterface $params
     *   The parameter bag
     * @param ClientInterface       $httpClient
     *   The http client
     */
    public function __construct(ParameterBagInterface $params, ClientInterface $httpClient)
    {
        $this->client = $httpClient;
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
     * @param int    $offset
     *   Result offset
     *
     * @return array
     *
     * @throws DataWellVendorException
     *   Throws DataWellVendorException on network error
     */
    public function search(string $query, int $offset = 1): array
    {
        // Validate that the service configuration have been set.
        if (empty($this->searchURL) || empty($this->user) || empty($this->password)) {
            throw new DataWellVendorException('Missing data well access configuration');
        }

        $more = false;
        $pidArray = [];

        try {
            $response = $this->client->request('POST', $this->searchURL, [
            RequestOptions::BODY => '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:open="http://oss.dbc.dk/ns/opensearch">
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
                       <open:stepValue>'.$this::SEARCH_LIMIT.'</open:stepValue>
                       <open:allRelations>1</open:allRelations>
                    <open:relationData>uri</open:relationData>
                    <outputType>json</outputType>
                    </open:searchRequest>
                 </soapenv:Body>
              </soapenv:Envelope>',
            ]);

            $content = $response->getBody()->getContents();
            $jsonResponse = json_decode($content, true);

            if (array_key_exists('searchResult', $jsonResponse['searchResponse']['result'])) {
                if ($jsonResponse['searchResponse']['result']['hitCount']['$']) {
                    $pidArray = $this->extractData($jsonResponse);
                }

                // It seems that the "more" in the search result is always "false".
                $more = true;
            } else {
                $more = false;
            }
        } catch (GuzzleException $exception) {
            throw new DataWellVendorException($exception->getMessage(), $exception->getCode());
        }

        return [$pidArray, $more, $offset + $this::SEARCH_LIMIT];
    }

    /**
     * Extract data from response.
     *
     * @param array $json
     *   Array of the json decoded data
     *
     * @return array
     *   Array of all pid => url pairs found in response
     */
    public function extractData(array $json): array
    {
        $data = [];

        foreach ($json['searchResponse']['result']['searchResult'] as $item) {
            foreach ($item['collection']['object'] as $object) {
                $pid = $object['identifier']['$'];
                $record = $object['record'];

                $title = array_key_exists('title', $record) ? $object['record']['title'][0]['$'] : null;
                $date = array_key_exists('date', $record) ? $object['record']['date'][0]['$'] : null;

                if ($title && $date) {
                    $data[$pid] = [];
                    $data[$pid]['title'] = $title;
                    $data[$pid]['date'] = $date;
                }
            }
        }

        return $data;
    }
}
