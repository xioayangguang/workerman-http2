<?php
declare(strict_types=1);

use parse\Grpc;

require_once __DIR__ . '/vendor/autoload.php';


//$grpc = new Grpc('ssl://0.0.0.0:444', "proto/pb", ['ssl' => [
//    'local_cert' => './example/key/draw.jiangtuan.cn_bundle.pem',
//    'local_pk' => './example/key/draw.jiangtuan.cn.key',
//]]);

$grpc = new Grpc('tcp://0.0.0.0:444', "proto/pb");
$grpc->name = 'Grpc';

Grpc::runAll();

//protoc --php_out=./   --workerman_out=.   ./proto/hello.proto
//protoc --php_out=./ ./proto/hello.proto
