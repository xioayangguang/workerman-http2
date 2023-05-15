<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use parse\Response;

require_once __DIR__ . '/vendor/autoload.php';

$h2 = new Http2('ssl://0.0.0.0:443', ['ssl' => [
    'local_cert' => './example/key/draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './example/key/draw.jiangtuan.cn.key',
]]);
$h2->name = 'Http2-h2';
$h2->onRequest = function (Request $request) {
    return new Response(200, ['content-type' => ['text/html'], 'a' => ['hello world']], "<h1>hello h2!<h1>");
};

if (!defined('GLOBAL_START')) {
    Http2::runAll();
}
