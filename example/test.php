<?php

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$task = new Worker('tcp://0.0.0.0:555');

$task->onMessage = function ($connection_to_baidu, $http_buffer) {
    var_dump($http_buffer);
};

$task->onWorkerStart = function ($task) {
    // 不支持直接指定http，但是可以用tcp模拟http协议发送数据
    $connection_to_baidu = new AsyncTcpConnection('tcp://127.0.0.1:1337');
    // 当连接建立成功时，发送http请求数据
    $connection_to_baidu->onConnect = function (AsyncTcpConnection $connection_to_baidu) {
        echo "connect success\n";
        $connection_to_baidu->send("GET / HTTP/1.1\r\nHost: 127.0.0.1:1337\r\nConnection: keep-alive\r\n\r\n");
    };
    $connection_to_baidu->onMessage = function (AsyncTcpConnection $connection_to_baidu, $http_buffer) {
        file_put_contents("test.txt", $http_buffer);
    };
    $connection_to_baidu->onClose = function (AsyncTcpConnection $connection_to_baidu) {
        echo "connection closed\n";
    };
    $connection_to_baidu->onError = function (AsyncTcpConnection $connection_to_baidu, $code, $msg) {
        echo "Error code:$code msg:$msg\n";
    };
    $connection_to_baidu->connect();
};
Worker::runAll();