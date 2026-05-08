<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Directory;

use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;
use PHPUnit\Framework\TestCase;

final class DirectoryUserTest extends TestCase
{
    public function testRejectsEmptyUserId(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryUser('   ');
    }

    public function testRejectsTooLongUserId(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryUser(str_repeat('1', 256));
    }

    public function testAcceptsCidr(): void
    {
        $user = new DirectoryUser('1001', cidr: '10.0.0.0/24');

        $this->assertSame('10.0.0.0/24', $user->cidr);
    }

    public function testAcceptsCacheableTrue(): void
    {
        $user = new DirectoryUser('1001', cacheable: true);

        $this->assertTrue($user->cacheable);
    }

    public function testAcceptsCacheablePositiveInteger(): void
    {
        $user = new DirectoryUser('1001', cacheable: 60000);

        $this->assertSame(60000, $user->cacheable);
    }

    public function testTreatsCacheableFalseAsNull(): void
    {
        $user = new DirectoryUser('1001', cacheable: false);

        $this->assertNull($user->cacheable);
    }

    public function testRejectsInvalidCacheableInteger(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryUser('1001', cacheable: 0);
    }

    public function testAcceptsTypePointer(): void
    {
        $user = new DirectoryUser('1001', type: 'pointer');

        $this->assertSame('pointer', $user->type);
    }

    public function testRejectsUnknownType(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryUser('1001', type: 'sofia');
    }
}
