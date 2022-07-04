<?php

namespace App\Tests\Api;

use App\Entity\Material;

class MaterialTest extends AbstractTest
{
    public function testGetCollection(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/materials');
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');

        $cover = new \stdClass();
        $cover->imageUrl = 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/822410-basis:94832393.jpg';
        $cover->size = 4855058;
        $cover->agencyId = '775100';

        $this->assertJsonContains([
            'isIdentifier' => '895730-basis:58212630',
            'isType' => 'PID',
            'agencyId' => '775100',
            'cover' => $cover,
        ]);
        $this->assertCount(20, $json);
    }

    public function testGetItem(): void
    {
        $iri = $this->findIriBy(Material::class, []);
        $response = $this->createClientWithCredentials()->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'isIdentifier' => '836460-basis:9280081',
            'isType' => 'PID',
            'agencyId' => '775100',
            'cover' => [
                'imageUrl' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/843200-basis:96826157.jpg',
                'size' => 8945324,
                'agencyId' => '775100'
            ]
        ]);
    }
}