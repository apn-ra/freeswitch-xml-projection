<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Directory;

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use PHPUnit\Framework\TestCase;

final class A1HashCredentialTest extends TestCase
{
    public function testComputesMd5FromPlainPassword(): void
    {
        $credential = A1HashCredential::fromPlainPassword('1001', 'example.test', 'secret');

        $params = $credential->toParams();

        $this->assertSame('a1-hash', $params[0]->name);
        $this->assertSame(md5('1001:example.test:secret'), $params[0]->value);
    }

    public function testRendersA1HashParam(): void
    {
        $credential = new A1HashCredential('abc123');

        $params = $credential->toParams();

        $this->assertCount(1, $params);
        $this->assertSame('a1-hash', $params[0]->name);
        $this->assertSame('abc123', $params[0]->value);
    }

    public function testRedactsDebugInfo(): void
    {
        $credential = new A1HashCredential('super-secret-hash');

        $this->assertSame(['hash' => '[redacted]'], $credential->__debugInfo());
    }

    public function testDoesNotExposeToString(): void
    {
        $this->assertNotContains('__toString', get_class_methods(new A1HashCredential('abc123')));
    }
}
