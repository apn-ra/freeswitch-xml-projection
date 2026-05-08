<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Directory;

use APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential;
use PHPUnit\Framework\TestCase;

final class PlainPasswordCredentialTest extends TestCase
{
    public function testRendersPasswordParam(): void
    {
        $credential = new PlainPasswordCredential('secret');

        $params = $credential->toParams();

        $this->assertCount(1, $params);
        $this->assertSame('password', $params[0]->name);
        $this->assertSame('secret', $params[0]->value);
    }

    public function testRedactsDebugInfo(): void
    {
        $credential = new PlainPasswordCredential('secret');

        $this->assertSame(['password' => '[redacted]'], $credential->__debugInfo());
    }

    public function testDoesNotExposeToString(): void
    {
        $this->assertNotContains('__toString', get_class_methods(new PlainPasswordCredential('secret')));
    }
}
