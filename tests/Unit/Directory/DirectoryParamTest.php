<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Directory;

use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;
use PHPUnit\Framework\TestCase;

final class DirectoryParamTest extends TestCase
{
    public function testRejectsEmptyParamName(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryParam('   ', 'value');
    }

    public function testRejectsTooLongParamName(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryParam(str_repeat('a', 129), 'value');
    }

    public function testRejectsInvalidXmlCharacters(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryParam('password', "bad\x07value");
    }

    public function testCreatesDefaultDialStringParam(): void
    {
        $param = DirectoryParam::dialStringDefault();

        $this->assertSame('dial-string', $param->name);
        $this->assertSame(
            '{presence_id=${dialed_user}@${dialed_domain}}${sofia_contact(${dialed_user}@${dialed_domain})}',
            $param->value,
        );
    }
}
