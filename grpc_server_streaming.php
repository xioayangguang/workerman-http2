<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use parse\Response;
use proto\pb\HelloRequest;
use proto\pb\HelloResponse;

require_once __DIR__ . '/vendor/autoload.php';

$http2 = new Http2('ssl://0.0.0.0:446', ['ssl' => [
    'local_cert' => './example/key/draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './example/key/draw.jiangtuan.cn.key',
    'verify_peer' => false,
    'allow_self_signed' => true,
]]);
$http2->name = 'GrpcServerStreaming';
//这个模式下服务端无法一次性获取Body体 (包括客户端流模式和双向流模式)
//这个模式下收到完整header后回及时返回header
$http2->setClientStreamUrl([
    "/pb.Greeter/DoubleSayHello",
    "/pb.Greeter/ClientSayHello"
]);

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
    $response_message->setReply('Hello1 ' . $obj->getName());
    $data = $response_message->serializeToString();
    $data = pack('CN', 0, strlen($data)) . $data;
    $response = new Response(200, [
        'content-type' => ['application/grpc'],
        'a' => ['hello header'],
    ], $data);
    $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
    return $response;
};

//只在普通模式和服务端流模式下生效， 在响应前hook body帧的写入 可处理grpc服务端流模式  此时header帧已经写入
$http2->onWriteBody = function (Request $request, Response $response) {
    $response_message = new HelloResponse();
    for ($i = 0; $i < 5; $i++) {
        $response_message->setReply('hahahah'.$i);
        $data = $response_message->serializeToString();
        $data = pack('CN', 0, strlen($data)) . $data;
        $response->tuckData($data);//在响应流前追加数据
    }
};

Http2::runAll();