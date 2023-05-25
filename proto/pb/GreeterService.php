<?php
declare(strict_types=1);
# source: hello.proto  

namespace proto\pb;


class GreeterService
{
	 public static $Streaming = [
		"client_streaming"=>["/pb.Greeter/ClientSayHello",],
		"double_streaming"=>["/pb.Greeter/DoubleSayHello",],
		"server_streaming"=>["/pb.Greeter/ServerSayHello",],
		"simple"=>["/pb.Greeter/SayHello",],
	];
	
	public static $Route  = [
		"/pb.Greeter/SayHello" => [GreeterService::class, "SayHello"],
		"/pb.Greeter/ClientSayHello" => [GreeterService::class, "ClientSayHello"],
		"/pb.Greeter/ServerSayHello" => [GreeterService::class, "ServerSayHello"],
		"/pb.Greeter/DoubleSayHello" => [GreeterService::class, "DoubleSayHello"],
	];

     public static $Parameter  = [
		"/pb.Greeter/SayHello" => HelloRequest::class,
		"/pb.Greeter/ClientSayHello" => HelloRequest::class,
		"/pb.Greeter/ServerSayHello" => HelloRequest::class,
		"/pb.Greeter/DoubleSayHello" => HelloRequest::class,
	];

    public const NAME = "pb.Greeter";
	
		
	/**
	* Simple
	* $request->metadata 获取metadata信息
	* @param HelloRequest $request
	* @return HelloResponse
	*/
	public static function SayHello(HelloRequest $request): HelloResponse 
    {
        //var_dump($request->metadata);
        $response_message = new HelloResponse();
        $response_message->setReply('Hello ' . $request->getName());
        return $response_message;
	}
		


	
		
	/**
	* ClientStreaming 一旦返回HelloResponse对象即表示关闭当前客户端流
	* $request->metadata 获取metadata信息
	* @param HelloRequest $request
	* @return HelloResponse|null
	*/
	public static function ClientSayHello(HelloRequest $request) : ?HelloResponse
    {
        $name = $request->getName();
        if ($name == "end") {
            $HelloResponse2 = new  HelloResponse();
            $HelloResponse2->setReply('helloEnd');
            return $HelloResponse2;
        } else {
            echo $name . "\r\n";;
        }
        return null;
	}
		
	

	
		
	/**
	* ServerStreaming
	* $request->metadata 获取metadata信息
	* @param HelloRequest $request
	* @return \Generator
	*/
	public static function ServerSayHello(HelloRequest $request):  \Generator
    {
        $name = $request->getName();
        echo $name . "\r\n";
        $HelloResponse3 = new HelloResponse();
        for ($i = 0; $i < 3; $i++) {
            $HelloResponse3->setReply('hello' . $i);
            yield $HelloResponse3;
        }
	}
		


	
		
	/**
    * DoubleStreaming如需结束流则设置HelloResponse->endStreaming = true;
	* $request->metadata 获取metadata信息
	* @param HelloRequest $request
	* @return \Generator
	*/
	public static function DoubleSayHello(HelloRequest $request) : \Generator
    {
        $name = $request->getName();
        echo $name . "\r\n";
        $HelloResponse2 = new  HelloResponse();
        if ($name == "end") {
            $HelloResponse2->setReply('hello end');
            $HelloResponse2->endStreaming = true;
            yield $HelloResponse2;
        } else {
            for ($i = 0; $i < 2; $i++) {
                $HelloResponse2->setReply('hello' . $i);
                yield $HelloResponse2;
            }
        }
	}
}
