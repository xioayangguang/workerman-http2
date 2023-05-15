<?php
# Generated by the protocol buffer compiler (https://github.com/mix-php/grpc). DO NOT EDIT!
# source: proto/hello.proto

namespace proto\pb;

use Mix\Grpc;
use Mix\Grpc\Context;

class GreeterClient extends Grpc\Client\AbstractClient
{
    /**
    * @param Context $context
    * @param HelloRequest $request
    * @param array $options
    * @return HelloResponse
    *
    * @throws Grpc\Exception\InvokeException
    */
    public function SayHello(Context $context, HelloRequest $request): HelloResponse
    {
        return $this->_simpleRequest('/pb.Greeter/SayHello', $context, $request, new HelloResponse());
    }

    /**
    * @param Context $context
    * @param HelloRequest $request
    * @param array $options
    * @return HelloResponse
    *
    * @throws Grpc\Exception\InvokeException
    */
    public function ClientSayHello(Context $context, HelloRequest $request): HelloResponse
    {
        return $this->_simpleRequest('/pb.Greeter/ClientSayHello', $context, $request, new HelloResponse());
    }

    /**
    * @param Context $context
    * @param HelloRequest $request
    * @param array $options
    * @return HelloResponse
    *
    * @throws Grpc\Exception\InvokeException
    */
    public function ServerSayHello(Context $context, HelloRequest $request): HelloResponse
    {
        return $this->_simpleRequest('/pb.Greeter/ServerSayHello', $context, $request, new HelloResponse());
    }

    /**
    * @param Context $context
    * @param HelloRequest $request
    * @param array $options
    * @return HelloResponse
    *
    * @throws Grpc\Exception\InvokeException
    */
    public function DoubleSayHello(Context $context, HelloRequest $request): HelloResponse
    {
        return $this->_simpleRequest('/pb.Greeter/DoubleSayHello', $context, $request, new HelloResponse());
    }
}
