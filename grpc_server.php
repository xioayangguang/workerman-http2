<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use Pb\HelloRequest;
use Pb\HelloResponse;

require_once __DIR__ . '/vendor/autoload.php';

new Http2('ssl://0.0.0.0:443', ['ssl' => [
    'local_cert' => './draw.jiangtuan.cn_bundle.pem',
    'local_pk' => './draw.jiangtuan.cn.key',
    'verify_peer' => false,
    'allow_self_signed' => true,
]], function (Request $request) {
    $data = $request->rawBody();
    $data = substr($data, 5);
    $obj = new HelloRequest();
    $obj->mergeFromString($data);
    $response_message = new HelloResponse();
    $response_message->setReply('Hello ' . $obj->getName());
    $data = $response_message->serializeToString();
    $data = pack('CN', 0, strlen($data)) . $data;
    $response = new \parse\Response(200, [
        'content-type' => ['application/grpc'],
        'a' => ['hello header'],
    ], $data);
    $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
    return $response;
});
Http2::runAll();
