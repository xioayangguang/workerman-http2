<?php
declare(strict_types=1);

use parse\Grpc;

require_once __DIR__ . '/vendor/autoload.php';

$http2 = new Grpc('ssl://0.0.0.0:443', "grpcHandler", ['ssl' => [
    'local_cert' => './example/key/draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './example/key/draw.jiangtuan.cn.key',
]]);
Grpc::runAll();

