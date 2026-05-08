<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;

require dirname(__DIR__) . '/vendor/autoload.php';

$document = new DirectoryDocument([
    new DirectoryDomain(
        'tenant.example.test',
        [DirectoryParam::dialStringDefault()],
        [],
        [
            new DirectoryUser(
                '1001',
                A1HashCredential::fromPlainPassword('1001', 'tenant.example.test', 'secret'),
            ),
        ],
    ),
]);

echo (new DirectoryXmlRenderer())->render($document);
