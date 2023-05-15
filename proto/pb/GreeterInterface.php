<?php
declare(strict_types=1);

namespace proto\pb;

interface GreeterInterface
{

    public const NAME = "pb.Greeter";

    /**
     * @param HelloRequest $request
     * @return HelloResponse
     */
    public function SayHello(HelloRequest $request): HelloResponse;

    /**
     * @param HelloRequest $request
     * @return HelloResponse
     */
    public function ClientSayHello(HelloRequest $request): HelloResponse;

    /**
     * @param HelloRequest $request
     * @return HelloResponse
     */
    public function ServerSayHello(HelloRequest $request): HelloResponse;

    /**
     * @param HelloRequest $request
     * @return HelloResponse
     */
    public function DoubleSayHello(HelloRequest $request): HelloResponse;
}
