<?php

namespace grpcHandler;

class Type
{
    const Type = [
        "simple" => [
            "/pb.Greeter/SayHello",
        ],
        "server_streaming" => [
            "/pb.Greeter/ServerSayHello",
        ],
        "double_streaming" => [
            "/pb.Greeter/DoubleSayHello",
        ],
        "client_streaming" => [
            "/pb.Greeter/ClientSayHello",
        ]
    ];

    public static $route = [
        "/pb.Greeter/DoubleSayHello" => [\grpcHandler\GreeterService::class, 'DoubleSayHello'],
        "/pb.Greeter/ClientSayHello" => [\grpcHandler\GreeterService::class, 'ClientSayHello'],
        "/pb.Greeter/ServerSayHello" => [\grpcHandler\GreeterService::class, 'ServerSayHello'],
        "/pb.Greeter/SayHello" => [\grpcHandler\GreeterService::class, 'SayHello'],
    ];


    public static $parameter = [
        "/pb.Greeter/DoubleSayHello" => 'your\namesapce\MyClass',
        "/pb.Greeter/DoubleSayHello" => 'your\namesapce\MyClass',
        "/pb.Greeter/DoubleSayHello" => 'your\namesapce\MyClass',
        "/pb.Greeter/DoubleSayHello" => 'your\namesapce\MyClass',
    ];
}
