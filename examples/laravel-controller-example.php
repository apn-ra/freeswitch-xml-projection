<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser;
use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;

// Example adaptation only. Laravel is not a package dependency.

$request = (new XmlCurlRequestParser())->parse(request()->all());

if (! $request->isDirectory() || $request->action()?->value !== 'sip_auth') {
    return response(XmlCurlResponse::notFound()->body, 200, XmlCurlResponse::notFound()->headers);
}

$document = new DirectoryDocument([
    new DirectoryDomain(
        $request->domain() ?? 'tenant.example.test',
        [DirectoryParam::dialStringDefault()],
        [],
        [
            new DirectoryUser(
                $request->user() ?? '1001',
                A1HashCredential::fromPlainPassword(
                    $request->user() ?? '1001',
                    $request->domain() ?? 'tenant.example.test',
                    'resolved-in-apntalk',
                ),
            ),
        ],
    ),
]);

$response = XmlCurlResponse::xml((new DirectoryXmlRenderer())->render($document));

return response($response->body, $response->statusCode, $response->headers);
