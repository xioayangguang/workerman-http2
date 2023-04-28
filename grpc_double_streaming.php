<?php
declare(strict_types=1);

use parse\Http2;
use Parse\Http2Stream;
use parse\Request;
use Parse\Response;
use Pb\HelloRequest;
use Pb\HelloResponse;

require_once __DIR__ . '/vendor/autoload.php';

$http2 = new Http2('ssl://0.0.0.0:448', ['ssl' => [
    'local_cert' => './key/draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './key/draw.jiangtuan.cn.key',
    'verify_peer' => false,
    'allow_self_signed' => true,
]]);
$http2->name = 'GrpcDoubleStreaming';
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

//只在grpc客户端流模式 和双向流模式下生效 每次有帧数据过来的时候 此时还没生成response对象
$http2->onStreamData = function (Http2Stream $stream, string $data) {
    static $_body = "";
    $_body .= $data;
    if (strlen($_body) < 5) return;
    $data = unpack('Cpack/Nleng', substr($_body, 0, 5));
    if (strlen($_body) < 5 + $data["leng"]) return;
    $obj = new HelloRequest();
    $obj->mergeFromString(substr($_body, 5, $data["leng"]));
    $_body = substr($_body, 5 + $data["leng"]);
    var_dump($obj->getName());
    $response_message = new HelloResponse();
    $response_message->setReply('回复客户端：' . $obj->getName());
    $data = $response_message->serializeToString();
    $data = pack('CN', 0, strlen($data)) . $data;
    $stream->sendStream($data);
};

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