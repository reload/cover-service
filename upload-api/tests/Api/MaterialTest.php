<?php

namespace App\Tests\Api;

use App\Entity\Cover;
use App\Entity\Material;
use Doctrine\ORM\EntityManager;

class MaterialTest extends AbstractTest
{
    private EntityManager $entityManager;

    public function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
    }

    public function testGetCollection(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/materials');
        $body = $response->getContent();
        $results = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');

        $cover = new \stdClass();
        $cover->imageUrl = 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/822410-basis:94832393.jpg';
        $cover->size = 4855058;
        $cover->agencyId = '775100';

        $this->assertJsonContains([
            [
                'isIdentifier' => '859340-basis:92302454',
                'isType' => 'PID',
                'agencyId' => '775100',
                'cover' => [
                    'imageUrl' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/749950-basis:50824753.jpg',
                    'size' => 4855058,
                    'agencyId' => '775100',
                ],
            ],
        ]);
        $this->assertCount(5, $results);
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
                'agencyId' => '775100',
            ],
        ]);
    }

    public function testPostItem(): void
    {
        $query = $this->entityManager->getRepository(Cover::class)->getHasNoMaterialQuery(1);
        /** @var Cover $cover */
        $cover = $query->getOneOrNullResult();
        $coverIri = $this->findIriBy(Cover::class, ['id' => $cover->getId()]);
        $response = $this->createClientWithCredentials()->request('POST', '/api/materials', [
            'json' => [
                'isIdentifier' => '836460-basis:9280082',
                'isType' => 'PID',
                'cover' => $coverIri,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
    }
}
