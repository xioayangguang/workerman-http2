<?php
declare(strict_types=1);
# source: hello.proto  

namespace Pb;


use parse\Grpc;
use parse\Http2Stream;
use parse\Response;


class GreeterService
{

    public const Streaming = [
        "client_streaming" => ["/pb.Greeter/ClientSayHello",],
        "double_streaming" => ["/pb.Greeter/DoubleSayHello",],
        "server_streaming" => ["/pb.Greeter/ServerSayHello",],
        "simple" => ["/pb.Greeter/SayHello",],
    ];

    public const  Route = [
        "/pb.Greeter/SayHello" => [GreeterService::class, "SayHello"],
        "/pb.Greeter/ClientSayHello" => [GreeterService::class, "ClientSayHello"],
        "/pb.Greeter/ServerSayHello" => [GreeterService::class, "ServerSayHello"],
        "/pb.Greeter/DoubleSayHello" => [GreeterService::class, "DoubleSayHello"],
    ];

    public const NAME = "pb.Greeter";

    /**
     *此处实现自己的业务逻辑
     * @param HelloRequest $request
     * @return HelloResponse
     */
    public static function SayHello(HelloRequest $request): HelloResponse
    {
        $response_message = new HelloResponse();
        $response_message->setReply('Hello ' . $request->getName());
        return $response_message;
    }


    /**
     *此处实现自己的业务逻辑
     * @param HelloRequest $request
     */
    public static function ClientSayHello(HelloRequest $request): HelloResponse
    {
        $response_message = new HelloResponse();
        $response_message->setReply('回复客户端：' . $request->getName());
        return $response_message;
    }


    /**
     *此处实现自己的业务逻辑
     * @param HelloRequest $request
     * @return HelloResponse
     */
    public static function ServerSayHello(HelloRequest $request, Response $response)
    {
        $response_message = new HelloResponse();
        for ($i = 0; $i < 5; $i++) {
            $response_message->setReply('hahahah' . $i);
            $data = Grpc::serializeToString($response_message);
            $response->tuckData($data);
        }
        //只要此函数返回，服务端流模式就结束
    }


    /**
     *此处实现自己的业务逻辑
     * @param HelloRequest $request
     */
    public static function DoubleSayHello(HelloRequest $request, Http2Stream $stream)
    {
        $response_message = new HelloResponse();
        for ($i = 0; $i < 5; $i++) {
            $response_message->setReply('回复客户端：' . $request->getName());
            $data = Grpc::serializeToString($response_message);
            $stream->sendStream($data);
        }
        $response_message->setReply('回复客户端：' . $request->getName());
        $data = Grpc::serializeToString($response_message);
        $stream->sendStream($data, true); //结束双向流
    }
}
