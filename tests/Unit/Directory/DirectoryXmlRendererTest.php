<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Directory;

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException;
use PHPUnit\Framework\TestCase;

final class DirectoryXmlRendererTest extends TestCase
{
    public function testRendersBasicUser(): void
    {
        $document = new DirectoryDocument([
            new DirectoryDomain('example.test', users: [
                new DirectoryUser('1001', credential: new A1HashCredential('hash-value')),
            ]),
        ]);

        $xml = (new DirectoryXmlRenderer())->render($document);

        $this->assertXmlStringEqualsXmlString($this->expectedBasicXml(), $xml);
    }

    public function testRendersUserWithParamsAndVariables(): void
    {
        $document = new DirectoryDocument([
            new DirectoryDomain(
                'example.test',
                params: [DirectoryParam::dialStringDefault()],
                variables: [new DirectoryVariable('domain_name', 'example.test')],
                users: [
                    new DirectoryUser(
                        '1001',
                        credential: new A1HashCredential('hash-value'),
                        params: [
                            new DirectoryParam('vm-password', '1234'),
                            new DirectoryParam('caller-id-number', '1001'),
                        ],
                        variables: [
                            new DirectoryVariable('user_context', 'tenant-1'),
                            new DirectoryVariable('toll_allow', 'domestic'),
                        ],
                    ),
                ],
            ),
        ]);

        $xml = (new DirectoryXmlRenderer())->render($document);

        $this->assertStringContainsString('<params>', $xml);
        $this->assertStringContainsString('<variables>', $xml);
        $this->assertTrue(strpos($xml, 'name="a1-hash"') < strpos($xml, 'name="vm-password"'));
        $this->assertTrue(strpos($xml, 'name="vm-password"') < strpos($xml, 'name="caller-id-number"'));
    }

    public function testEscapesXmlSpecialCharacters(): void
    {
        $document = new DirectoryDocument([
            new DirectoryDomain('example.test', users: [
                new DirectoryUser(
                    '1001',
                    credential: new A1HashCredential('hash&<>"\''),
                    params: [new DirectoryParam('caller-id-name', 'Alice & Bob <Team>')],
                    variables: [new DirectoryVariable('accountcode', 'tenant>"1"')],
                ),
            ]),
        ]);

        $xml = (new DirectoryXmlRenderer())->render($document);

        $this->assertStringContainsString('hash&amp;&lt;&gt;&quot;\'', $xml);
        $this->assertStringContainsString('Alice &amp; Bob &lt;Team&gt;', $xml);
        $this->assertStringContainsString('tenant&gt;&quot;1&quot;', $xml);
    }

    public function testRejectsInvalidXmlControlCharacters(): void
    {
        $this->expectException(InvalidProjectionException::class);

        new DirectoryParam('vm-password', "bad\x01value");
    }

    public function testRendersCredentialParamsBeforeExtraParams(): void
    {
        $document = new DirectoryDocument([
            new DirectoryDomain('example.test', users: [
                new DirectoryUser(
                    '1001',
                    credential: new A1HashCredential('hash-value'),
                    params: [
                        new DirectoryParam('vm-password', '1234'),
                        new DirectoryParam('caller-id-number', '1001'),
                    ],
                ),
            ]),
        ]);

        $xml = (new DirectoryXmlRenderer())->render($document);

        $this->assertTrue(strpos($xml, 'name="a1-hash"') < strpos($xml, 'name="vm-password"'));
        $this->assertTrue(strpos($xml, 'name="vm-password"') < strpos($xml, 'name="caller-id-number"'));
    }

    public function testPreservesCallerParamAndVariableOrder(): void
    {
        $document = new DirectoryDocument([
            new DirectoryDomain('example.test', users: [
                new DirectoryUser(
                    '1001',
                    params: [
                        new DirectoryParam('first', '1'),
                        new DirectoryParam('second', '2'),
                    ],
                    variables: [
                        new DirectoryVariable('alpha', '1'),
                        new DirectoryVariable('beta', '2'),
                    ],
                ),
            ]),
        ]);

        $xml = (new DirectoryXmlRenderer())->render($document);

        $this->assertTrue(strpos($xml, 'name="first"') < strpos($xml, 'name="second"'));
        $this->assertTrue(strpos($xml, 'name="alpha"') < strpos($xml, 'name="beta"'));
    }

    public function testOutputIsDeterministic(): void
    {
        $document = new DirectoryDocument([
            new DirectoryDomain('example.test', users: [
                new DirectoryUser('1001', credential: new A1HashCredential('hash-value')),
            ]),
        ]);

        $renderer = new DirectoryXmlRenderer();

        $this->assertSame($renderer->render($document), $renderer->render($document));
    }

    private function expectedBasicXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<document type="freeswitch/xml">
  <section name="directory">
    <domain name="example.test">
      <users>
        <user id="1001">
          <params>
            <param name="a1-hash" value="hash-value"/>
          </params>
        </user>
      </users>
    </domain>
  </section>
</document>
XML;
    }
}
