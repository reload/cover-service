<?php

/**
 * @file
 * Test cases for the Open Platform authentication service.
 */

namespace Tests\Service\OpenPlatform;

use App\Exception\MaterialTypeException;
use App\Exception\OpenPlatformAuthException;
use App\Exception\OpenPlatformSearchException;
use App\Service\OpenPlatform\AuthenticationService;
use App\Service\OpenPlatform\SearchService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Class SearchServiceTest.
 */
class SearchServiceTest extends TestCase
{
    public const TOKEN = 'fde1432d66d33e4cq66e5ad04757811e47864329';
    public const IDENTIFIER = '9788770531214';

    /**
     * Test that a search response is parsed correctly.
     *
     * @throws MaterialTypeException
     * @throws OpenPlatformSearchException
     * @throws OpenPlatformAuthException
     */
    public function testSearch()
    {
        $body = '{"statusCode":200,"data":[{"title":["Tempelridderen"],"creator":["Jan Guillou"],"date":["2008"],"publisher":["Modtryk"],"pid":["870970-basis:27073301"],"identifierISBN":["9788770531214"]}],"hitCount":4,"more":false}';
        $service = $this->getAuthenticationService(false, $body);
        $material = $service->search($this::IDENTIFIER, 'isbn', '', '', true);

        // Test basic information.
        $this->assertEquals('Tempelridderen', $material->getTitle());
        $this->assertEquals('Jan Guillou', $material->getCreator());
        $this->assertEquals('2008', $material->getDate());
        $this->assertEquals('Modtryk', $material->getPublisher());

        // Test that pid have been sat.
        $id = $material->getIdentifierByType('pid');
        $id = reset($id);
        $this->assertEquals('870970-basis:27073301', $id->getId());
        $this->assertEquals('pid', $id->getType());

        // Test that isbn have been sat.
        $id = $material->getIdentifierByType('isbn');
        $id = reset($id);
        $this->assertEquals('9788770531214', $id->getId());
        $this->assertEquals('isbn', $id->getType());
    }

    /**
     * Build mocks to inject into the search service.
     *
     * @param bool $cacheHit
     *   Should we use the cache
     * @param string $body
     *   The response to get from http request
     *
     * @return searchService
     *   Mocked search service
     */
    private function getAuthenticationService(bool $cacheHit, string $body): SearchService
    {
        // Setup basic cache.
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->any())
            ->method('get')
            ->willReturn('');

        if ($cacheHit) {
            $cacheItem->expects($this->once())
                ->method('isHit')
                ->willReturn($cacheHit);
        }

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $authentication = $this->createMock(AuthenticationService::class);
        $authentication->expects($this->any())
            ->method('getAccessToken')
            ->willReturn($this::TOKEN);

        $client = new MockHttpClient([
            new MockResponse($body, ['http_code' => empty($body) ? 500 : 200]),
        ]);

        return new SearchService($cache, $authentication, $client, 'https://search.local/', 'opac', '775100', 50, 600);
    }
}
