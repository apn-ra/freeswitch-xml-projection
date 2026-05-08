<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser;
use APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer;
use APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential;

require dirname(__DIR__) . '/vendor/autoload.php';

$document = new DirectoryDocument([
    new DirectoryDomain(
        'tenant.example.test',
        [DirectoryParam::dialStringDefault()],
        [],
        [
            new DirectoryUser('1001', new PlainPasswordCredential('secret')),
        ],
    ),
]);

echo (new DirectoryXmlRenderer())->render($document);
