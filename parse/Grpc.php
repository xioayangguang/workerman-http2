<?php

namespace parse;

use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Grpc extends Http2
{

    private static $_body = "";
    private static $streaming = [];
    private static $route = [];
    private static $Parameter = [];
    /**
     * @var string
     */
    public static $protocPath = "";


    public function __construct($socket_name, $protocPath, array $context_option = [])
    {
        parent::__construct($socket_name, $context_option);
        $this->onStreamData = [$this, "onStreamData"];
        $this->onRequest = [$this, "onRequest"];
        $this->onWriteBody = [$this, "onWriteBody"];
        self::$protocPath = $protocPath;
        self::loadHook();
        $this->clientStreamUrl = array_merge(self::$streaming["client_streaming"], self::$streaming["double_streaming"]);
    }

    public static function pack(string $data): string
    {
        return pack('CN', 0, strlen($data)) . $data;
    }


    public static function unpack(string $data): string
    {
        self::$_body .= $data;
        if (strlen(self::$_body) < 5) return "";
        $data = unpack('Cpack/Nleng', substr(self::$_body, 0, 5));
        if (strlen(self::$_body) < 5 + $data["leng"]) return "";
        $grpcData = substr(self::$_body, 5, $data["leng"]);
        self::$_body = substr(self::$_body, 5 + $data["leng"]);
        return $grpcData;
    }


    public static function onStreamData(Request $request, Http2Stream $stream, string $data)
    {
        $data = self::unpack($data);
        if (!empty($data)) {
            if (is_callable(self::$route[$request->path()])) {
                $obj = new self::$Parameter[$request->path()];
                $obj->mergeFromString($data);
                $obj->metadata = $request->getHeaders();
                $response_message = (self::$route[$request->path()])($obj);
                if (in_array($request->path(), self::$streaming["client_streaming"])) {
                    if (!empty($response_message)) {
                        $data = $response_message->serializeToString();
                        $stream->sendStream(self::pack($data));
                        return false;
                    }
                }
                if (in_array($request->path(), self::$streaming["double_streaming"])) {
                    $generator = $response_message;
                    if ($generator instanceof Iterator) {
                        foreach ($generator as $response_message) {
                            $data = $response_message->serializeToString();
                            $stream->sendStream(self::pack($data));   //在响应流前追加数据
                        }
                    } else {//终止流
                        return false;
                    }
                    if (isset($response_message->endStreaming) and $response_message->endStreaming === true) {//终止流
                        return false;
                    }
                }
            } else {//这里不传入end  如果是结束直接传Response对象
                $stream->sendStream(self::pack("404"));
                return false;
            }
        }
    }


    public static function onRequest(Request $request)
    {
        if (in_array($request->path(), self::$streaming["simple"])) {//普通模式
            $data = self::unpack($request->rawBody());
            if (!empty($data)) {
                if (is_callable(self::$route[$request->path()])) {
                    $obj = new  self::$Parameter[$request->path()];
                    $obj->metadata = $request->getHeaders();
                    $obj->mergeFromString($data);
                    $response_message = (self::$route[$request->path()])($obj);
                    $data = $response_message->serializeToString();
                    $response = new Response(200, ['content-type' => ['application/grpc'],], self::pack($data));
                    $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
                    return $response;
                } else {
                    $response = new Response(200, ['content-type' => ['application/grpc']], self::pack(""));
                    $response->setTrailers(["grpc-status" => "5", "grpc-message" => ""]);
                    return $response;
                }
            }
        } else { //先返回响应普通头
            $response = new Response(200, ['content-type' => ['application/grpc'],], "");//默认响应成功
            $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
            return $response;
        }
    }


    public static function onWriteBody(Request $request, Response $response)
    {
        if (in_array($request->path(), self::$streaming["server_streaming"])) {
            if (is_callable(self::$route[$request->path()])) {
                $obj = new self::$Parameter[$request->path()];
                $obj->metadata = $request->getHeaders();
                $obj->mergeFromString(self::unpack($request->rawBody()));
                $generator = (self::$route[$request->path()])($obj);
                if ($generator instanceof Iterator) {
                    foreach ($generator as $response_message) {
                        $data = $response_message->serializeToString();
                        $response->tuckData(self::pack($data));   //在响应流前追加数据
                    }
                }
            }
        }
    }

    public static function serializeToString($response): string
    {
        if (method_exists($response, 'encode')) {
            $data = $response->encode();
        } elseif (method_exists($response, 'serializeToString')) {
            $data = $response->serializeToString();
        } else {
            $data = $response->serialize();
        }
        return self::pack($data);
    }

    /**
     * 扫描文件
     * @return array
     */
    public static function loadHook()
    {
        $base_path = realpath(__DIR__ . '/../');
        $path = $base_path . '/' . self::$protocPath . '/';
        if (!file_exists($path)) {
            throw new \Exception("protoc path set error");
        }
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            $path_info = pathinfo($file);
            if ($path_info['extension'] != 'php') continue;
            $file = "{$path_info['dirname']}/{$path_info['filename']}";
            $file = str_replace("/", "\\", $file);
            $file = substr($file, strlen($base_path));
            if (property_exists($file, 'Streaming') and property_exists($file, 'Route') and property_exists($file, 'Parameter')) {
                self::$streaming = array_merge(self::$streaming, $file::$Streaming);
                self::$route = array_merge(self::$route, $file::$Route);
                self::$Parameter = array_merge(self::$Parameter, $file::$Parameter);
            }
        }
    }
}