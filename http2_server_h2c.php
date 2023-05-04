<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use parse\Response;

require_once __DIR__ . '/vendor/autoload.php';

$http2 = new Http2('tcp://0.0.0.0:80');

$http2->onRequest = function (Request $request) {
    return new Response(200, ['content-type' => ['text/html'], 'a' => ['hello world']], "<h1>hello h2!<h1>");
};
$http2->name = 'http2-h2c';
Http2::runAll();
