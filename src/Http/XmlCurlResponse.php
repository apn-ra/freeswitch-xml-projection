<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Http;

use APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer;

final readonly class XmlCurlResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $body,
        public int $statusCode = 200,
        public array $headers = ['Content-Type' => 'text/xml; charset=UTF-8'],
    ) {}

    public static function xml(string $body): self
    {
        return new self($body);
    }

    public static function notFound(): self
    {
        return new self((new ResultXmlRenderer())->renderNotFound());
    }
}
