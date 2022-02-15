<?php

namespace Spip\Archiver\Tests;

use PHPUnit\Framework\TestCase;
use Spip\Archiver\AbstractArchiver;

/**
 * @covers \Spip\Archiver\AbstractArchiver
 *
 * @internal
 */
class AbstractArchiverTest extends TestCase
{


    public static function setUpBeforeClass(): void
    {
        @mkdir(__DIR__ . '/../var/tmp/directory');
        @file_put_contents(__DIR__ . '/../var/tmp/directory/test.txt', 'contenu de test');
        @mkdir(__DIR__ . '/../var/tmp/directory/sub_directory');
        @file_put_contents(__DIR__ . '/../var/tmp/directory/sub_directory/test2.txt', 'contenu de test2');
        @file_put_contents(__DIR__ . '/../var/tmp/directory/sub_directory/test3.txt', 'contenu de test3');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(__DIR__ . '/../var/tmp/directory/sub_directory/test3.txt');
        @unlink(__DIR__ . '/../var/tmp/directory/sub_directory/test2.txt');
        @rmdir(__DIR__ . '/../var/tmp/directory/sub_directory');
        @unlink(__DIR__ . '/../var/tmp/directory/test.txt');
        @rmdir(__DIR__ . '/../var/tmp/directory');
    }

    public function testConstructor()
    {
        // Given
        $stub = $this->getMockForAbstractClass(
            AbstractArchiver::class,
            ['']
        );
        $stub->expects($this->any())
            ->method('informer')
            ->will($this->returnValue([]));

        // When
        $actual = $stub->informer();

        // Then
        $this->assertEquals([], $actual);
        $this->assertEquals(0, $stub->erreur());
        $this->assertEquals('OK', $stub->message());
        $this->assertTrue($stub->getLectureSeule());
    }

    public function dataListerFichiers()
    {
        $dir = __DIR__ . '/../var/tmp/directory';
        return [
            'one-file' => [
                [$dir. '/test.txt'],
                [$dir. '/test.txt']
            ],
            'two-files' => [
                [$dir . '/test.txt', $dir . '/sub_directory/test2.txt'],
                [$dir . '/test.txt', $dir . '/sub_directory/test2.txt']
            ],
            'one-dir' => [
                [$dir . '/sub_directory/test2.txt', $dir . '/sub_directory/test3.txt'],
                [$dir . '/sub_directory'],
            ],
            'one-dir-with-subdir' => [
                [
                    $dir . '/sub_directory/test2.txt', 
                    $dir . '/sub_directory/test3.txt',
                    $dir . '/test.txt', 
                ],
                [$dir],
            ],
        ];
    }

    /**
     * @dataProvider dataListerFichiers
     */
    public function testListerFichiers($expectedFiles, $paths) 
    {
        // given
        $stub = $this->getMockForAbstractClass(
            AbstractArchiver::class,
            ['']
        );

        $class = new \ReflectionClass($stub);
        $method = $class->getMethod('listerFichiers');
        $method->setAccessible(true);

        // when
        $actual = $method->invoke($stub, $paths);

        // Then
        $this->assertEquals($expectedFiles, $actual);    
    }
}
