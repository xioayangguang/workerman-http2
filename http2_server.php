<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;

require_once __DIR__ . '/vendor/autoload.php';

new Http2('ssl://0.0.0.0:443', ['ssl' => [
    'local_cert' => './draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './draw.jiangtuan.cn.key',
    'verify_peer' => false,
    'allow_self_signed' => true,
]], function (Request $request) {
    return new \parse\Response(200,
        [
            'content-type' => ['application/json; charset=utf-8'],
            'a' => ['hello world'],
            'b' => ['b']
        ],
        $request->getMethod() . ":hello h2!"
    );
});
Http2::runAll();