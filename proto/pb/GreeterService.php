<?php
declare(strict_types=1);
# source: hello.proto

namespace proto\pb;

class GreeterService
{
    public static $Streaming = [
        "client_streaming" => ["/pb.Greeter/ClientSayHello",],
        "double_streaming" => ["/pb.Greeter/DoubleSayHello",],
        "server_streaming" => ["/pb.Greeter/ServerSayHello",],
        "simple" => ["/pb.Greeter/SayHello",],
    ];

    public static $Route = [
        "/pb.Greeter/SayHello" => [GreeterService::class, "SayHello"],
        "/pb.Greeter/ClientSayHello" => [GreeterService::class, "ClientSayHello"],
        "/pb.Greeter/ServerSayHello" => [GreeterService::class, "ServerSayHello"],
        "/pb.Greeter/DoubleSayHello" => [GreeterService::class, "DoubleSayHello"],
    ];

    public static $Parameter = [
        "/pb.Greeter/SayHello" => HelloRequest::class,
        "/pb.Greeter/ClientSayHello" => HelloRequest::class,
        "/pb.Greeter/ServerSayHello" => HelloRequest::class,
        "/pb.Greeter/DoubleSayHello" => HelloRequest::class,
    ];

    public const NAME = "pb.Greeter";

    /**
     *此处实现自己的业务逻辑
     * Simple
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
     * ClientStreaming 一旦返回HelloResponse2对象就表示结束当前流
     * @param HelloRequest $request
     */
    public static function ClientSayHello(HelloRequest $request): ?HelloResponse
    {
        $name = $request->getName();
        if ($name == "end") {
            $HelloResponse2 = new  HelloResponse();
            $HelloResponse2->setReply('helloEnd');
            return $HelloResponse2;
        } else {
            var_dump($name);
        }
        return null;
    }


    /**
     *此处实现自己的业务逻辑
     * ServerStreaming
     * @param HelloRequest $request
     */
    public static function ServerSayHello(HelloRequest $request): \Generator
    {
        $name = $request->getName();
        var_dump($name);
        $HelloResponse3 = new HelloResponse();
        for ($i = 0; $i < 3; $i++) {
            $HelloResponse3->setReply('hello' . $i);
            yield $HelloResponse3;
        }
    }


    /**
     *此处实现自己的业务逻辑
     * DoubleStreaming  如需要结束流则设置  $HelloResponse4->endStreaming = true;
     * @param HelloRequest $request
     */
    public static function DoubleSayHello(HelloRequest $request): \Generator
    {
        $name = $request->getName();
        var_dump($name);
        $HelloResponse2 = new  HelloResponse();
        if ($name == "end") {
            $HelloResponse2->setReply('hello end');
            $HelloResponse2->endStreaming = true;
            yield $HelloResponse2;
        } else {
            for ($i = 0; $i < 5; $i++) {
                $HelloResponse2->setReply('hello' . $i);
                yield $HelloResponse2;
            }
        }
    }
}
