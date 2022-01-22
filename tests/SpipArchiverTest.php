<?php

namespace Spip\Archiver\Tests;

use PHPUnit\Framework\TestCase;
use Spip\Archiver\SpipArchiver;

/**
 * @covers \Spip\Archiver\AbstractArchiver
 * @covers \Spip\Archiver\SpipArchiver
 * @covers \Spip\Archiver\ZipArchive
 * @covers \Spip\Archiver\TarArchive
 * @covers \Spip\Archiver\TgzArchive
 * @covers \Spip\Archiver\NoDotFilterIterator
 *
 * @internal
 */
class SpipArchiverTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        @mkdir(__DIR__ . '/../var/tmp/directory');
        @file_put_contents(__DIR__ . '/../var/tmp/directory/test.txt', 'contenu de test');


		$test_retirer_zip = new \ZipArchive();
		$test_retirer_zip->open(__DIR__ . '/../var/tmp/retirer.zip', \ZipArchive::CREATE);
		$test_retirer_zip->addFromString('test.txt', 'contenu de test');
		$test_retirer_zip->close();
		@mkdir(__DIR__ . '/../var/tmp/tar/directory');
        @file_put_contents(__DIR__ . '/../var/tmp/tar/directory/test.txt', 'contenu de test');
		@mkdir(__DIR__ . '/../var/tmp/tar/directory/sub_directory');
        @file_put_contents(__DIR__ . '/../var/tmp/tar/directory/sub_directory/test2.txt', 'contenu de test2');
		$test_retirer_tar = new \Spip\Archiver\TarArchive();
		$test_retirer_tar->open(__DIR__ . '/../var/tmp/tar/retirer.tar', 'creation');
		$test_retirer_tar->compress(__DIR__ . '/../var/tmp/tar/directory', [
			__DIR__ . '/../var/tmp/tar/directory/test.txt',
			__DIR__ . '/../var/tmp/tar/directory/sub_directory/test2.txt',
		]);
		@unlink(__DIR__ . '/../var/tmp/tar/directory/test.txt');
        @unlink(__DIR__ . '/../var/tmp/tar/directory/sub_directory/test2.txt');
		@rmdir(__DIR__ . '/../var/tmp/tar/directory/sub_directory');
		@rmdir(__DIR__ . '/../var/tmp/tar/directory');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(__DIR__ . '/../var/tmp/test.txt');
        @unlink(__DIR__ . '/../var/tmp/tar/directory/test.txt');
        @rmdir(__DIR__ . '/../var/tmp/tar/directory');
        @unlink(__DIR__ . '/../var/tmp/tgz/directory/test.txt');
        @rmdir(__DIR__ . '/../var/tmp/tgz/directory');
        @unlink(__DIR__ . '/../var/tmp/directory/test.txt');
        @rmdir(__DIR__ . '/../var/tmp/directory');
        @unlink(__DIR__ . '/../var/tmp/emballer.zip');
        @unlink(__DIR__ . '/../var/tmp/emballer2.zip');
        @unlink(__DIR__ . '/../var/tmp/tar/emballer.tar');
        @unlink(__DIR__ . '/../var/tmp/retirer.zip');
        @unlink(__DIR__ . '/../var/tmp/tar/retirer.tar');
    }

    public function dataInformer()
    {
        return [
            'empty-string' => [
                3,
                [
                    'proprietes' => [],
                    'fichiers' => [],
                ],
                '',
                '',
            ],
            'unknown' => [
                2,
                [
                    'proprietes' => [],
                    'fichiers' => [],
                ],
                __DIR__ . '/../var/tmp/file.unknown',
                '',
            ],
            'exotic' => [
                2,
                [
                    'proprietes' => [],
                    'fichiers' => [],
                ],
                __DIR__ . '/../var/tmp/file.unknown',
                'exotic',
            ],
            'zip' => [
                0,
                [
                    'proprietes' => [
                        'racine' => '',
                    ],
                    'fichiers' => [
                        [
                            'filename' => 'test.txt',
                            'size' => 16,
                        ],
                    ],
                ],
                __DIR__ . '/../var/tmp/test.zip',
                '',
            ],
            'tar' => [
                0,
                [
                    'proprietes' => [
                        'racine' => '/directory/',
                    ],
                    'fichiers' => [
                        [
                            'filename' => '/directory/test.txt',
                            'size' => 16,
                        ],
                    ],
                ],
                __DIR__ . '/../var/tmp/tar/test.tar',
                '',
            ],
			'empty-tar' => [
				0,
				[
                    'proprietes' => [
						'racine' => '',
					],
                    'fichiers' => [],
                ],
				__DIR__ . '/../var/tmp/tar/empty.tar',
				'tar',
			],
            'tgz' => [
                0,
                [
                    'proprietes' => [
                        'racine' => '/directory/',
                    ],
                    'fichiers' => [
                        [
                            'filename' => '/directory/test.txt',
                            'size' => 16,
                        ],
                    ],
                ],
                __DIR__ . '/../var/tmp/tgz/test.tar.gz',
                '',
            ],
        ];
    }

    /**
     * @dataProvider dataInformer
     */
    public function testInformer($expected, $expectedList, $file, $extension)
    {
        // Given
        $archiver = new SpipArchiver($file, $extension);

        // When
        $actual = $archiver->informer();

        //Then
        $this->assertEquals($expectedList, $actual);
        $this->assertEquals($expected, $archiver->erreur());
    }

    public function dataDeballer()
    {
        return [
            'zip' => [
                true,
                0,
                __DIR__ . '/../var/tmp/test.zip',
                __DIR__ . '/../var/tmp',
                [],
                __DIR__ . '/../var/tmp/test.txt',
            ],
			'destination-not-exists' => [
                false,
                5,
                __DIR__ . '/../var/tmp/test.zip',
                '',
                [__DIR__ . '/../var/tmp/directory/test.txt'],
                __DIR__ . '/../var/tmp/directory' . md5(mt_rand()),
            ],
            'tar' => [
                true,
                0,
                __DIR__ . '/../var/tmp/tar/test.tar',
                __DIR__ . '/../var/tmp/tar',
                [],
                __DIR__ . '/../var/tmp/tar/directory/test.txt',
            ],
            'tgz' => [
                true,
                0,
                __DIR__ . '/../var/tmp/tgz/test.tar.gz',
                __DIR__ . '/../var/tmp/tgz',
                ['directory/test.txt'],
                __DIR__ . '/../var/tmp/tgz/directory/test.txt',
            ],
        ];
    }

    /**
     * @dataProvider dataDeballer
     */
    public function testDeballer($expected, $expectedErreur, $file, $target, $files, $testFile)
    {
        // Given
        $archiver = new SpipArchiver($file);

        // When
        $actual = $archiver->deballer($target, $files);

        //Then
        $this->assertEquals($expected, $actual, 'decompress ok');
        $this->assertEquals($expectedErreur, $archiver->erreur(), 'error code');
        $this->assertTrue($expectedErreur === 5 || file_exists($testFile), 'decompressed file exists');
    }

    public function dataEmballer()
    {
        return [
            'exists' => [
                false,
                6,
                __DIR__ . '/../var/tmp/test.zip',
                '',
                [__DIR__ . '/../var/tmp/directory/test.txt'],
                __DIR__ . '/../var/tmp/directory',
            ],
            'source-not-exists' => [
                false,
                7,
                __DIR__ . '/../var/tmp/test.zip',
                '',
                [__DIR__ . '/../var/tmp/directory/test.txt'],
                __DIR__ . '/../var/tmp/directory' . md5(mt_rand()),
            ],
            'zip' => [
                true,
                0,
                __DIR__ . '/../var/tmp/emballer.zip',
                '',
                [__DIR__ . '/../var/tmp/directory/test.txt'],
                __DIR__ . '/../var/tmp/directory',
            ],
            'zip2' => [
                true,
                0,
                __DIR__ . '/../var/tmp/emballer2.zip',
                '',
                [__DIR__ . '/../var/tmp/directory/test.txt'],
                null,
            ],
            'tar' => [
				true,
				0,
				__DIR__ . '/../var/tmp/tar/emballer.tar',
				'',
				[__DIR__ . '/../var/tmp/directory/test.txt'],
                __DIR__ . '/../var/tmp/directory',
            ],
        ];
    }

    /**
     * @dataProvider dataEmballer
     */
    public function testEmballer($expected, $expectedErreur, $file, $extension, $files, $target)
    {
        // Given
        $archiver = new SpipArchiver($file, $extension);

        // When
        $actual = $archiver->emballer($files, $target);

        //Then
        $this->assertEquals($expected, $actual, 'compress ok');
        $this->assertEquals($expectedErreur, $archiver->erreur(), 'error code');
        $this->assertTrue(file_exists($file), 'compressed file exists');
    }

	public function dataRetirer()
    {
        return [
            'not-exists' => [
                false,
                3,
                md5(mt_rand()),
                'zip',
                ['test.txt'],
            ],
            'zip' => [
                true,
                0,
                __DIR__ . '/../var/tmp/retirer.zip',
                '',
                ['test.txt'],
            ],
            'tar' => [
                true,
                0,
                __DIR__ . '/../var/tmp/tar/retirer.tar',
                '',
                ['directory/test.txt'],
            ],
        ];
    }

    /**
     * @dataProvider dataRetirer
     */
    public function testRetirer($expected, $expectedErreur, $file, $extension, $files)
    {
        // Given
        $archiver = new SpipArchiver($file, $extension);

        // When
        $actual = $archiver->retirer($files);

        //Then
        $this->assertEquals($expected, $actual, 'remove ok');
        $this->assertEquals($expectedErreur, $archiver->erreur(), 'error code');
    }
}
