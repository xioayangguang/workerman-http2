<?php
declare(strict_types=1);
# source: hello.proto

namespace grpcHandler;

use Pb\HelloRequest1;
use Pb\HelloRequest2;
use Pb\HelloRequest3;
use Pb\HelloRequest4;
use Pb\HelloResponse1;
use Pb\HelloResponse2;
use Pb\HelloResponse3;
use Pb\HelloResponse4;

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
        "/pb.Greeter/SayHello" => HelloRequest1::class,
        "/pb.Greeter/ClientSayHello" => HelloRequest2::class,
        "/pb.Greeter/ServerSayHello" => HelloRequest3::class,
        "/pb.Greeter/DoubleSayHello" => HelloRequest4::class,
    ];

    public const NAME = "pb.Greeter";

    /**
     *此处实现自己的业务逻辑
     * Simple
     * @param HelloRequest1 $request
     * @return HelloResponse1
     */
    public static function SayHello(HelloRequest1 $request): HelloResponse1
    {
        $response_message = new HelloResponse1();
        $response_message->setReply('Hello ' . $request->getName());
        return $response_message;
    }

    /**
     *此处实现自己的业务逻辑
     * ClientStreaming 一旦返回HelloResponse2对象就表示结束当前流
     * @param HelloRequest2 $request
     */
    public static function ClientSayHello(HelloRequest2 $request): ?HelloResponse2
    {
        $name = $request->getName();
        if ($name == "end") {
            $HelloResponse2 = new  HelloResponse2();
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
     * @param HelloRequest3 $request
     */
    public static function ServerSayHello(HelloRequest3 $request): \Generator
    {
        $name = $request->getName();
        var_dump($name);
        $HelloResponse3 = new HelloResponse3();
        for ($i = 0; $i < 3; $i++) {
            $HelloResponse3->setReply('hello' . $i);
            yield $HelloResponse3;
        }
    }


    /**
     *此处实现自己的业务逻辑
     * DoubleStreaming  如需要结束流则设置  $HelloResponse4->endStreaming = true;
     * @param HelloRequest4 $request
     */
    public static function DoubleSayHello(HelloRequest4 $request): \Generator
    {
        $name = $request->getName();
        var_dump($name);
        $HelloResponse2 = new  HelloResponse4();
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
