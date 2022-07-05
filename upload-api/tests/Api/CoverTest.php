<?php

namespace App\Tests\Api;

use App\Entity\Cover;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CoverTest extends AbstractTest
{
    public function testGetCollection(): void
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/covers');
        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $this->assertJsonContains([
            [
                'imageUrl' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/712810-basis:44991213.jpg',
                'size' => 8945324,
                'agencyId' => '775100',
            ],
        ]);
        $this->assertCount(10, $json);
    }

    public function testGetItem(): void
    {
        $iri = $this->findIriBy(Cover::class, []);
        $response = $this->createClientWithCredentials()->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $this->getIDFromIri($iri),
            'imageUrl' => 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/843200-basis:96826157.jpg',
            'size' => 8945324,
            'agencyId' => '775100',
        ]);
    }

    public function testUpload(): void
    {
        // Move test file content into tmp file as the file upload will remove the source file. WTF.
        $tmpFile = stream_get_meta_data(tmpfile())['uri'];
        file_put_contents($tmpFile, file_get_contents('fixtures/files/test.jpg'));
        $file = new UploadedFile($tmpFile, 'test.jpg');

        $response = $this->createClientWithCredentials()->request('POST', '/api/covers', [
            'extra' => [
                'files' => [
                    'cover' => $file,
                ],
            ],
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $body = $response->getContent();
        $json = \json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $pos = \strrpos($json->imageUrl, '/');
        $filename = \substr($json->imageUrl, $pos + 1);
        $filename = $this->getPublicFile($filename);

        $this->assertTrue(\file_exists($filename));
        \unlink($filename);
    }

    protected static function getProjectDir(): string
    {
        return !empty($GLOBALS['app']) ? $GLOBALS['app']->getKernel()->getProjectDir() : getcwd();
    }

    protected static function getPublicFile(string $file): string
    {
        return self::getProjectDir().'/public/cover/'.$file;
    }
}
