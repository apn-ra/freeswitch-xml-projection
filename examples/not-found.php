<?php

declare(strict_types=1);

use APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse;

require dirname(__DIR__) . '/vendor/autoload.php';

echo XmlCurlResponse::notFound()->body;
