<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Integration;

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;
use APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlCurlRequestParser::class)]
#[CoversClass(DirectoryDocument::class)]
#[CoversClass(DirectoryDomain::class)]
#[CoversClass(DirectoryUser::class)]
#[CoversClass(DirectoryParam::class)]
#[CoversClass(DirectoryVariable::class)]
#[CoversClass(A1HashCredential::class)]
#[CoversClass(DirectoryXmlRenderer::class)]
#[CoversClass(ResultXmlRenderer::class)]
#[CoversClass(XmlCurlResponse::class)]
final class DirectoryProjectionTest extends TestCase
{
    public function testItParsesTheRealLikeRedactedSipAuthFixture(): void
    {
        $request = (new XmlCurlRequestParser())->parse($this->requestFixture('real-directory-sip-auth-redacted'));

        self::assertTrue($request->isDirectory());
        self::assertSame('1001', $request->user());
        self::assertSame('172.21.204.105', $request->domain());
        self::assertSame('DESKTOP-PC01', $request->freeSwitchHostname());
        self::assertSame('internal', $request->profile());
        self::assertSame('apntalk-fixture-capture/0.1', $request->sipUserAgent());
        self::assertSame('[redacted]', $request->redacted()['sip_auth_response']);
        self::assertSame('[redacted]', $request->redacted()['sip_auth_nonce']);
        self::assertSame('sip_auth', $request->raw()['action']);
        self::assertSame('sofia_reg_parse_auth', $request->raw()['Event-Calling-Function']);
    }

    public function testItRendersFixtureStableDirectoryXmlForA1HashCredential(): void
    {
        $document = new DirectoryDocument(domains: [
            new DirectoryDomain(
                name: 'tenant-123.sip.apntalk.test',
                params: [
                    DirectoryParam::dialStringDefault(),
                ],
                variables: [
                    new DirectoryVariable('domain_uuid', 'tenant-123'),
                ],
                users: [
                    new DirectoryUser(
                        id: '1001',
                        credential: A1HashCredential::fromPlainPassword(
                            username: '1001',
                            domain: 'tenant-123.sip.apntalk.test',
                            password: 's3cret-a1',
                        ),
                        params: [
                            new DirectoryParam('vm-password', '1001'),
                            new DirectoryParam('caller-id-in-from', 'true'),
                        ],
                        variables: [
                            new DirectoryVariable('user_context', 'tenant-123'),
                            new DirectoryVariable('effective_caller_id_name', 'Agent & Reception'),
                            new DirectoryVariable('effective_caller_id_number', '1001'),
                        ],
                        cacheable: 60000,
                    ),
                ],
            ),
        ]);

        $renderer = new DirectoryXmlRenderer();

        $firstRender = $renderer->render($document);
        $secondRender = $renderer->render($document);

        self::assertSame($firstRender, $secondRender);
        self::assertXmlStringEqualsXmlFile($this->responseFixturePath('directory-sip-auth-a1-hash.xml'), $firstRender);
    }

    public function testItRendersFixtureStableDirectoryXmlForPlainPasswordCredential(): void
    {
        $document = new DirectoryDocument(domains: [
            new DirectoryDomain(
                name: 'demo.sip.apntalk.test',
                params: [
                    DirectoryParam::dialStringDefault(),
                ],
                users: [
                    new DirectoryUser(
                        id: '2002',
                        credential: new PlainPasswordCredential('pw-demo-2002'),
                        params: [
                            new DirectoryParam('vm-password', '2002'),
                            new DirectoryParam('toll_allow', 'domestic,international'),
                        ],
                        variables: [
                            new DirectoryVariable('user_context', 'demo'),
                            new DirectoryVariable('accountcode', 'demo:2002'),
                        ],
                    ),
                ],
            ),
        ]);

        self::assertXmlStringEqualsXmlFile(
            $this->responseFixturePath('directory-sip-auth-plain-password.xml'),
            (new DirectoryXmlRenderer())->render($document),
        );
    }

    public function testItRendersNotFoundXmlThroughTheResultRendererAndResponseWrapper(): void
    {
        $xml = (new ResultXmlRenderer())->renderNotFound();
        $response = XmlCurlResponse::notFound();

        self::assertXmlStringEqualsXmlFile($this->responseFixturePath('not-found.xml'), $xml);
        self::assertSame(200, $response->statusCode);
        self::assertSame(['Content-Type' => 'text/xml; charset=UTF-8'], $response->headers);
        self::assertXmlStringEqualsXmlFile($this->responseFixturePath('not-found.xml'), $response->body);
    }

    /**
     * @return array<string, scalar|null>
     */
    private function requestFixture(string $name): array
    {
        /** @var array<string, scalar|null> $fixture */
        $fixture = require $this->fixturePath('Requests/' . $name . '.php');

        return $fixture;
    }

    private function responseFixturePath(string $name): string
    {
        return $this->fixturePath('Responses/' . $name);
    }

    private function fixturePath(string $relativePath): string
    {
        return dirname(__DIR__) . '/Fixture/' . $relativePath;
    }
}
