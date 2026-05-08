<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Directory;

use APNTalk\FreeSwitchXmlProjection\Exception\XmlRenderingException;
use APNTalk\FreeSwitchXmlProjection\Internal\XmlValueValidator;
use XMLWriter;

final class DirectoryXmlRenderer
{
    public function render(DirectoryDocument $document): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        if ($xml->startDocument('1.0', 'UTF-8', 'no') === false) {
            throw new XmlRenderingException('Failed to start XML document.');
        }

        $this->startElement($xml, 'document');
        $this->writeAttribute($xml, 'type', 'freeswitch/xml');

        $this->startElement($xml, 'section');
        $this->writeAttribute($xml, 'name', 'directory');

        foreach ($document->domains as $domain) {
            $this->renderDomain($xml, $domain);
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function renderDomain(XMLWriter $xml, DirectoryDomain $domain): void
    {
        $this->startElement($xml, 'domain');
        $this->writeAttribute($xml, 'name', $domain->name);

        if ($domain->params !== []) {
            $this->startElement($xml, 'params');

            foreach ($domain->params as $param) {
                $this->renderParam($xml, $param);
            }

            $xml->endElement();
        }

        if ($domain->variables !== []) {
            $this->startElement($xml, 'variables');

            foreach ($domain->variables as $variable) {
                $this->renderVariable($xml, $variable);
            }

            $xml->endElement();
        }

        $this->startElement($xml, 'users');

        foreach ($domain->users as $user) {
            $this->renderUser($xml, $user);
        }

        $xml->endElement();
        $xml->endElement();
    }

    private function renderUser(XMLWriter $xml, DirectoryUser $user): void
    {
        $this->startElement($xml, 'user');
        $this->writeAttribute($xml, 'id', $user->id);

        if ($user->cidr !== null) {
            $this->writeAttribute($xml, 'cidr', $user->cidr);
        }

        if ($user->cacheable === true) {
            $this->writeAttribute($xml, 'cacheable', 'true');
        } elseif (is_int($user->cacheable)) {
            $this->writeAttribute($xml, 'cacheable', (string) $user->cacheable);
        }

        if ($user->type !== null) {
            $this->writeAttribute($xml, 'type', $user->type);
        }

        $userParams = [];

        if ($user->credential !== null) {
            foreach ($user->credential->toParams() as $param) {
                $userParams[] = $param;
            }
        }

        foreach ($user->params as $param) {
            $userParams[] = $param;
        }

        if ($userParams !== []) {
            $this->startElement($xml, 'params');

            foreach ($userParams as $param) {
                $this->renderParam($xml, $param);
            }

            $xml->endElement();
        }

        if ($user->variables !== []) {
            $this->startElement($xml, 'variables');

            foreach ($user->variables as $variable) {
                $this->renderVariable($xml, $variable);
            }

            $xml->endElement();
        }

        $xml->endElement();
    }

    private function renderParam(XMLWriter $xml, DirectoryParam $param): void
    {
        $this->startElement($xml, 'param');
        $this->writeAttribute($xml, 'name', $param->name);
        $this->writeAttribute(
            $xml,
            'value',
            XmlValueValidator::normalizeScalarValue($param->value, sprintf('Directory param "%s" value', $param->name)),
        );
        $xml->endElement();
    }

    private function renderVariable(XMLWriter $xml, DirectoryVariable $variable): void
    {
        $this->startElement($xml, 'variable');
        $this->writeAttribute($xml, 'name', $variable->name);
        $this->writeAttribute(
            $xml,
            'value',
            XmlValueValidator::normalizeScalarValue($variable->value, sprintf('Directory variable "%s" value', $variable->name)),
        );
        $xml->endElement();
    }

    private function startElement(XMLWriter $xml, string $name): void
    {
        if ($xml->startElement($name) === false) {
            throw new XmlRenderingException(sprintf('Failed to start <%s> element.', $name));
        }
    }

    private function writeAttribute(XMLWriter $xml, string $name, string $value): void
    {
        XmlValueValidator::assertNoInvalidXmlControlCharacters($value, sprintf('XML attribute "%s"', $name));

        if ($xml->writeAttribute($name, $value) === false) {
            throw new XmlRenderingException(sprintf('Failed to write "%s" attribute.', $name));
        }
    }
}
