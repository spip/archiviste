<?php

namespace Spip\Archiver\Tests;

use PHPUnit\Framework\TestCase;
use Spip\Archiver\AbstractArchiver;

// si on lance les tests depuis tests/
if (function_exists('include_spip')) {
	include_spip('inc/archives');
}

/**
 * @covers \Spip\Archiver\AbstractArchiver
 *
 * @internal
 */
class AbstractArchiverTest extends TestCase
{
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
}
