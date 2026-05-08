<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Http;

use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlCurlResponse::class)]
final class XmlCurlResponseTest extends TestCase
{
    public function testXmlResponseUsesStatus200(): void
    {
        self::assertSame(200, XmlCurlResponse::xml('<xml/>')->statusCode);
    }

    public function testXmlResponseUsesExpectedContentType(): void
    {
        self::assertSame(
            ['Content-Type' => 'text/xml; charset=UTF-8'],
            XmlCurlResponse::xml('<xml/>')->headers,
        );
    }

    public function testNotFoundReturnsStatus200(): void
    {
        self::assertSame(200, XmlCurlResponse::notFound()->statusCode);
    }

    public function testNotFoundBodyIsValidResultXml(): void
    {
        self::assertXmlStringEqualsXmlFile(
            dirname(__DIR__, 2) . '/Fixture/Responses/not-found.xml',
            XmlCurlResponse::notFound()->body,
        );
    }
}
