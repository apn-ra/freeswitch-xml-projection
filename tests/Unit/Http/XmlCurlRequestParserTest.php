<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Http;

use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction;
use APNTalk\FreeSwitchXmlProjection\Enum\DirectoryPurpose;
use APNTalk\FreeSwitchXmlProjection\Enum\XmlCurlSection;
use APNTalk\FreeSwitchXmlProjection\Exception\InvalidXmlCurlRequestException;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(XmlCurlRequest::class)]
#[CoversClass(XmlCurlRequestParser::class)]
final class XmlCurlRequestParserTest extends TestCase
{
    public function testItParsesDirectorySection(): void
    {
        $request = $this->parseFixture('directory-sip-auth-minimal');

        self::assertSame(XmlCurlSection::Directory, $request->section());
        self::assertTrue($request->isDirectory());
    }

    public function testItParsesSipAuthAction(): void
    {
        self::assertSame(DirectoryAction::SipAuth, $this->parseFixture('directory-sip-auth-minimal')->action());
    }

    public function testItParsesUppercaseActionWithoutImplyingSupport(): void
    {
        self::assertSame(DirectoryAction::ReverseAuthLookup, $this->parseFixture('reverse-auth-lookup')->action());
    }

    public function testItParsesPurposeGateways(): void
    {
        self::assertSame(DirectoryPurpose::Gateways, $this->parseFixture('directory-gateways')->purpose());
    }

    public function testItNormalizesEmptyStringsToNullForKnownLogicalFields(): void
    {
        $request = (new XmlCurlRequestParser())->parse([
            'section' => '',
            'action' => '',
            'user' => '',
            'domain' => '',
            'ip' => '',
        ]);

        self::assertNull($request->section());
        self::assertNull($request->action());
        self::assertNull($request->user());
        self::assertNull($request->domain());
        self::assertNull($request->ip());
    }

    public function testItPreservesUnknownScalarFields(): void
    {
        $request = (new XmlCurlRequestParser())->parse([
            'section' => 'directory',
            'custom' => 'value',
            'count' => 3,
        ]);

        self::assertSame('value', $request->raw()['custom']);
        self::assertSame(3, $request->raw()['count']);
    }

    public function testItRejectsArrayValues(): void
    {
        $this->expectException(InvalidXmlCurlRequestException::class);

        (new XmlCurlRequestParser())->parse(['section' => ['directory']]);
    }

    public function testItRejectsObjectValues(): void
    {
        $this->expectException(InvalidXmlCurlRequestException::class);

        (new XmlCurlRequestParser())->parse(['section' => new stdClass()]);
    }

    public function testItRejectsResourceValues(): void
    {
        $resource = fopen('php://memory', 'rb');
        self::assertIsResource($resource);

        try {
            $this->expectException(InvalidXmlCurlRequestException::class);
            (new XmlCurlRequestParser())->parse(['section' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function testItPreservesRawFields(): void
    {
        $request = $this->parseFixture('real-directory-sip-auth-redacted');

        self::assertSame('REQUEST_PARAMS', $request->raw()['Event-Name']);
    }

    public function testItAppliesActionOverUppercaseAction(): void
    {
        $request = (new XmlCurlRequestParser())->parse([
            'section' => 'directory',
            'action' => 'sip_auth',
            'Action' => 'reverse-auth-lookup',
        ]);

        self::assertSame(DirectoryAction::SipAuth, $request->action());
    }

    public function testItAppliesUserOverSipAuthUsername(): void
    {
        $request = (new XmlCurlRequestParser())->parse([
            'section' => 'directory',
            'user' => 'explicit',
            'sip_auth_username' => 'fallback',
        ]);

        self::assertSame('explicit', $request->user());
    }

    public function testItAppliesDomainOverSipAuthRealm(): void
    {
        $request = (new XmlCurlRequestParser())->parse([
            'section' => 'directory',
            'domain' => 'explicit.test',
            'sip_auth_realm' => 'fallback.test',
        ]);

        self::assertSame('explicit.test', $request->domain());
    }

    public function testItMapsFreeSwitchHostname(): void
    {
        self::assertSame(
            'fs-edge-01.apntalk.test',
            $this->parseFixture('real-directory-sip-auth-redacted')->freeSwitchHostname(),
        );
    }

    public function testItReturnsNullForUnknownEnumValues(): void
    {
        $request = (new XmlCurlRequestParser())->parse([
            'section' => 'unknown',
            'action' => 'unsupported',
            'purpose' => 'custom',
        ]);

        self::assertNull($request->section());
        self::assertNull($request->action());
        self::assertNull($request->purpose());
        self::assertFalse($request->isDirectory());
    }

    private function parseFixture(string $name): XmlCurlRequest
    {
        /** @var array<string, mixed> $fixture */
        $fixture = require dirname(__DIR__, 2) . '/Fixture/Requests/' . $name . '.php';

        return (new XmlCurlRequestParser())->parse($fixture);
    }
}
