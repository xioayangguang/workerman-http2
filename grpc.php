<?php
declare(strict_types=1);

use parse\Grpc;

require_once __DIR__ . '/vendor/autoload.php';

$grpc = new Grpc('ssl://0.0.0.0:444', "proto/pb", ['ssl' => [
    'local_cert' => './example/key/draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './example/key/draw.jiangtuan.cn.key',
]]);
$grpc->name = 'Grpc';
if (!defined('GLOBAL_START')) {
    Grpc::runAll();
}

//protoc --php_out=./  --grpc_out=./ --plugin=protoc-gen-grpc=C:\Users\93002\go\bin\grpc_php_plugin.exe ./proto/hello.proto
//protoc --php_out=./   --workerman_out=.   ./proto/hello.proto
//protoc --php_out=./ ./proto/hello.proto
