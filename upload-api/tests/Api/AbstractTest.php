<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use ApiPlatform\Core\Exception\RuntimeException;
use App\DataFixtures\Faker\Provider\CoverProvider;
use DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\User;
use Faker\Factory;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractTest extends ApiTestCase
{
    use RefreshDatabaseTrait;
    private ?string $token = null;
    private ?Client $clientWithCredentials = null;
    private AdapterInterface $tokenCache;
    private Filesystem $filesystem;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->tokenCache = $container->get('token.cache');
        $this->filesystem = $container->get('filesystem');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        CoverProvider::cleanupFiles('test');
    }

    protected function findIriBy(string $resourceClass, array $criteria): ?string
    {
        $iri = parent::findIriBy($resourceClass, $criteria); // TODO: Change the autogenerated stub

        if (null === $iri) {
            throw new RuntimeException('No iri found for class: '.$resourceClass);
        }

        return strstr($iri, '/');
    }

    protected function getIdFromIri(string $iri): int
    {
        $pos = \strrpos($iri, '/');

        if (false === $pos) {
            throw new RuntimeException('No / found in iri');
        }

        $id = \substr($iri, $pos + 1);

        return (int) $id;
    }

    protected function createClientWithCredentials(): Client
    {
        if ($this->clientWithCredentials) {
            return $this->clientWithCredentials;
        }

        $this->clientWithCredentials = static::createClient([], [
            'headers' => [
                'authorization' => 'Bearer '.$this->getToken(),
                'accept' => 'application/json',
            ],
        ]);

        return $this->clientWithCredentials;
    }

    protected function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $faker = Factory::create();

        $token = $faker->sha1();
        $clientId = $faker->uuid();

        $user = new User();
        $user->setActive(true);
        $user->setToken($token);
        $user->setExpires(new \DateTime('now + 1 day'));
        $user->setAgency('775100');
        $user->setAuthType('anonymous');
        $user->setClientId($clientId);

        // By caching a valid user under a known token we 'hack' the provider.
        // @see DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\OpenPlatformUserProvider
        $item = $this->tokenCache->getItem($token);
        $item->set($user);
        $this->tokenCache->save($item);

        return $token;
    }
}
