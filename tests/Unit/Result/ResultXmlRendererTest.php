<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Unit\Result;

use APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer;
use PHPUnit\Framework\TestCase;

final class ResultXmlRendererTest extends TestCase
{
    public function testRendersNotFoundDocument(): void
    {
        $xml = (new ResultXmlRenderer())->renderNotFound();

        $this->assertXmlStringEqualsXmlString($this->expectedXml(), $xml);
    }

    public function testUsesFreeswitchXmlDocumentType(): void
    {
        $xml = (new ResultXmlRenderer())->renderNotFound();

        $this->assertStringContainsString('<document type="freeswitch/xml">', $xml);
    }

    public function testUsesResultSection(): void
    {
        $xml = (new ResultXmlRenderer())->renderNotFound();

        $this->assertStringContainsString('<section name="result">', $xml);
    }

    public function testUsesNotFoundStatus(): void
    {
        $xml = (new ResultXmlRenderer())->renderNotFound();

        $this->assertStringContainsString('<result status="not found"/>', $xml);
    }

    public function testOutputIsDeterministic(): void
    {
        $renderer = new ResultXmlRenderer();

        $this->assertSame($renderer->renderNotFound(), $renderer->renderNotFound());
    }

    private function expectedXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<document type="freeswitch/xml">
  <section name="result">
    <result status="not found"/>
  </section>
</document>
XML;
    }
}
