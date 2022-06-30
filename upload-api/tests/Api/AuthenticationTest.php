<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\AbstractTest;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use http\Client;

class AuthenticationTest extends AbstractTest
{
    use ReloadDatabaseTrait;

    public function testLogin(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', '/cover');
        $this->assertResponseIsSuccessful();
    }

//    private function getAuthenticatedClient: Client
//    {
//        $client = self::createClient();
//
//        $client->request(
//
//        )
//    }
}