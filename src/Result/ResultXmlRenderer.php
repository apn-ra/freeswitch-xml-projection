<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Result;

use APNTalk\FreeSwitchXmlProjection\Exception\XmlRenderingException;
use XMLWriter;

final class ResultXmlRenderer
{
    public function renderNotFound(): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        if ($xml->startDocument('1.0', 'UTF-8', 'no') === false) {
            throw new XmlRenderingException('Failed to start XML document.');
        }

        if ($xml->startElement('document') === false) {
            throw new XmlRenderingException('Failed to start <document> element.');
        }

        if ($xml->writeAttribute('type', 'freeswitch/xml') === false) {
            throw new XmlRenderingException('Failed to write document type attribute.');
        }

        if ($xml->startElement('section') === false) {
            throw new XmlRenderingException('Failed to start <section> element.');
        }

        if ($xml->writeAttribute('name', 'result') === false) {
            throw new XmlRenderingException('Failed to write result section name attribute.');
        }

        if ($xml->startElement('result') === false) {
            throw new XmlRenderingException('Failed to start <result> element.');
        }

        if ($xml->writeAttribute('status', 'not found') === false) {
            throw new XmlRenderingException('Failed to write result status attribute.');
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
