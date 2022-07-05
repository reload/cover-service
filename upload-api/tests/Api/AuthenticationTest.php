<?php

namespace App\Tests\Api;

class AuthenticationTest extends AbstractTest
{
    public function testDocsAccess(): void
    {
        $response = static::createClient()->request('GET', '/api', [
            'headers' => [
                'accept' => 'text/html',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testLoginCovers(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/covers');
        $this->assertResponseIsSuccessful();
    }

    public function testLoginMaterials(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/materials');
        $this->assertResponseIsSuccessful();
    }

    public function testAccessDenied(): void
    {
        $response = static::createClient()->request('GET', '/api/covers', [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }
}
