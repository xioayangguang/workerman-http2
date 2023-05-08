<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use parse\Response;
use proto\pb\HelloRequest;
use proto\pb\HelloResponse;

require_once __DIR__ . '/vendor/autoload.php';

$http2 = new Http2('ssl://0.0.0.0:444', ['ssl' => [
    'local_cert' => './example/key/draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './example/key/draw.jiangtuan.cn.key',
    'verify_peer' => false,
    'allow_self_signed' => true,
]]);
$http2->name = 'GrpcSimple';
//Grpc简单模式 onRequest
//Grpc服务端流模式 onRequest + onWriteBody
//Grpc客户端流模式 onStreamData + onRequest
//Grpc双向流模式 onStreamData + onRequest

//收到了完整的请求 有end_Stream才会调此函数  处理grpc简单模式
$http2->onRequest = function (Request $request) {
    $data = $request->rawBody();
    $data = substr($data, 5);
    $obj = new HelloRequest();
    $obj->mergeFromString($data);
    $response_message = new HelloResponse();
    $response_message->setReply('Hello ' . $obj->getName());
    $data = $response_message->serializeToString();
    $data = pack('CN', 0, strlen($data)) . $data;
    $response = new Response(200, [
        'content-type' => ['application/grpc'],
        'a' => ['hello header'],
    ], $data);
    $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
    return $response;
};

Http2::runAll();