<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;

class CoverProvider extends Base
{
    private const FILES = [
        '/fixtures/files/test.jpg',
        '/fixtures/files/test_1.jpg',
        '/fixtures/files/test_2.jpg',
        '/fixtures/files/test_3.jpg',
    ];

    /**
     * Get random image url for the cloudinary CDN.
     *
     * @return string
     *   A random realistic CDN URL
     */
    public function remoteUrl(): string
    {
        return 'https://res.cloudinary.com/dandigbib/image/upload/v1543609481//FixturesData/'.$this->pid().'.jpg';
    }

    public function filePath(): string
    {
        return $this->generator->lexify('?????????????').$this->pid().'.jpeg';
    }

    public function agencyId(): int
    {
        return self::numberBetween(70000, 90000) * 10;
    }

    public static function randomImage(string $env = 'dev'): string
    {
        $file = self::getProjectDir().self::FILES[array_rand(self::FILES)];
        $new = self::getProjectDir().'/public/cover/'.$env.'_fixture_'.str_shuffle(sha1((string) time())).'.jpg';
        file_put_contents($new, file_get_contents($file));

        return basename($new);
    }

    public static function fileSha(string $file): string
    {
        return sha1(self::getPublicFile($file));
    }

    public static function imageSize(string $file): int
    {
        return filesize(self::getPublicFile($file));
    }

    public static function imageWidth(string $file): int
    {
        return getimagesize(self::getPublicFile($file))[0];
    }

    public static function imageHeight(string $file): int
    {
        return getimagesize(self::getPublicFile($file))[1];
    }

    public static function fileMimeType(string $file): string
    {
        return getimagesize(self::getPublicFile($file))['mime'];
    }

    /**
     * Get a random pid number.
     *
     * @return string
     *   A random but pseudo realistic pid identifier
     */
    public function pid(): string
    {
        $libraryNumber = self::numberBetween(70000, 90000) * 10;

        return $libraryNumber.'-basis:'.self::randomNumber(8);
    }

    private static function getProjectDir(): string
    {
        return !empty($GLOBALS['app']) ? $GLOBALS['app']->getKernel()->getProjectDir() : getcwd();
    }

    private static function getPublicFile(string $file): string
    {
        return self::getProjectDir().'/public/cover/'.$file;
    }

    public static function cleanupFiles(string $env = 'dev'): void
    {
        $files = \scandir(self::getProjectDir().'/public/cover/');

        foreach ($files as $file) {
            $filename = self::getProjectDir().'/public/cover/'.$file;
            if (\str_starts_with($file, $env.'_fixture_') && \is_file($filename)) {
                \unlink($filename);
            }
        }
    }
}
