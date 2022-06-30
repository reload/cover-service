<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use DanskernesDigitaleBibliotek\AgencyAuthBundle\Security\User;
use Faker\Factory;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;

abstract class AbstractTest extends ApiTestCase
{
    private string $token;
    private ?Client $clientWithCredentials;
    private AdapterInterface $tokenCache;

    use RefreshDatabaseTrait;

    public function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->tokenCache = $container->get('token.cache');
    }

    protected function createClientWithCredentials(): Client
    {
        if ($this->clientWithCredentials) {
            return $this->clientWithCredentials;
        }

        $this->clientWithCredentials = static::createClient([], ['headers' => ['authorization' => 'Bearer '.$this->getToken()]]);

        return $this->clientWithCredentials;
    }

    protected function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $faker = Factory::create();

        $token = $faker->sha1;
        $clientId = $faker->uuid;

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