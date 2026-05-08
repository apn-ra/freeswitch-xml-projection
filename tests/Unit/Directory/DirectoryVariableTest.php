<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Directory;

use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable;
use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;
use PHPUnit\Framework\TestCase;

final class DirectoryVariableTest extends TestCase
{
    public function testRejectsEmptyVariableName(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryVariable('   ', 'value');
    }

    public function testRejectsTooLongVariableName(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryVariable(str_repeat('a', 129), 'value');
    }

    public function testRejectsInvalidXmlCharacters(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryVariable('user_context', "bad\x07value");
    }
}
