<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Security;

use APNTalk\FreeSwitchXmlProjection\Security\Redactor;
use APNTalk\FreeSwitchXmlProjection\Security\SensitiveFieldList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Redactor::class)]
#[CoversClass(SensitiveFieldList::class)]
final class RedactorTest extends TestCase
{
    public function testItRedactsSensitiveFieldsCaseInsensitively(): void
    {
        $redacted = (new Redactor())->redact([
            'Authorization' => 'Basic abc',
            'sip_auth_nonce' => 'nonce',
            'vm-password' => '1001',
            'other' => 'value',
        ]);

        self::assertSame('[redacted]', $redacted['Authorization']);
        self::assertSame('[redacted]', $redacted['sip_auth_nonce']);
        self::assertSame('[redacted]', $redacted['vm-password']);
        self::assertSame('value', $redacted['other']);
    }
}
