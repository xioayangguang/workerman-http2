<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use Parse\Response;

require_once __DIR__ . '/vendor/autoload.php';

$http2 = new Http2('ssl://0.0.0.0:443', ['ssl' => [
    'local_cert' => './draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './draw.jiangtuan.cn.key',
    'verify_peer' => false,
    'allow_self_signed' => true,
]]);

$http2->onRequest = function (Request $request) {
    return new Response(200, [
        'content-type' => ['text/html'],
        'a' => ['hello world'],
        'b' => ['b']
    ], "<h1>hello h2!<h1>");
};
Http2::runAll();
