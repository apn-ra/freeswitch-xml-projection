<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Http;

use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryPurpose;
use APNTalk\FreeSwitchXmlProjection\Enum\XmlCurlSection;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlCurlRequest::class)]
final class XmlCurlRequestTest extends TestCase
{
    public function testRawReturnsPreservedScalarFields(): void
    {
        $request = $this->request();

        self::assertSame('value', $request->raw()['custom']);
    }

    public function testRedactedReturnsRedactedScalarFields(): void
    {
        $request = $this->request();

        self::assertSame('[redacted]', $request->redacted()['Authorization']);
    }

    public function testIsDirectoryIsTrueForDirectorySection(): void
    {
        self::assertTrue($this->request()->isDirectory());
    }

    public function testIsDirectoryIsFalseForUnknownSection(): void
    {
        $request = new XmlCurlRequest([], [], ['section' => 'custom']);

        self::assertFalse($request->isDirectory());
    }

    public function testUnknownEnumValuesReturnNull(): void
    {
        $request = new XmlCurlRequest([], [], [
            'section' => 'custom',
            'action' => 'custom',
            'purpose' => 'custom',
        ]);

        self::assertNull($request->section());
        self::assertNull($request->action());
        self::assertNull($request->purpose());
    }

    public function testTypedAccessorsResolveKnownValues(): void
    {
        $request = $this->request();

        self::assertSame(XmlCurlSection::Directory, $request->section());
        self::assertSame(DirectoryAction::SipAuth, $request->action());
        self::assertSame(DirectoryPurpose::Gateways, (new XmlCurlRequest([], [], ['purpose' => 'gateways']))->purpose());
        self::assertSame('1001', $request->user());
        self::assertSame('tenant.example.test', $request->domain());
        self::assertSame('198.51.100.10', $request->ip());
        self::assertSame('fs01.example.test', $request->freeSwitchHostname());
        self::assertSame('Example UA', $request->sipUserAgent());
    }

    private function request(): XmlCurlRequest
    {
        return new XmlCurlRequest(
            [
                'custom' => 'value',
                'Authorization' => 'Basic secret',
            ],
            [
                'custom' => 'value',
                'Authorization' => '[redacted]',
            ],
            [
                'section' => 'directory',
                'action' => 'sip_auth',
                'user' => '1001',
                'domain' => 'tenant.example.test',
                'ip' => '198.51.100.10',
                'FreeSWITCH-Hostname' => 'fs01.example.test',
                'sip_user_agent' => 'Example UA',
            ],
        );
    }
}
