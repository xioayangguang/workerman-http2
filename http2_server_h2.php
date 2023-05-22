<?php
declare(strict_types=1);

use parse\Http2;
use parse\Request;
use parse\Response;

require_once __DIR__ . '/vendor/autoload.php';

$h2 = new Http2('ssl://0.0.0.0:443', ['ssl' => [
    'local_cert' => './example-go-client/key/admin.jiangtuan.cn_bundle.pem',
    'local_pk' => './example-go-client/key/admin.jiangtuan.cn.key',
]]);
$h2->name = 'Http2-h2';
$h2->onRequest = function (Request $request) {
    //var_dump($request->getMethod());
    //var_dump($request->path());
    //var_dump($request->get());
    //var_dump($request->post());
    //var_dump($request->file());
    //return new Response(200, ['A' => 'hello world'], "<h1>hello h2!<h1>");
    //return new Response(200, ['content-type' => ['text/html'], 'a' => ['hello world']], file_get_contents("./static/index.html"));
    $path = $request->path();
    if ($path === '/') {
        $response = new Response(200, ["content-type" => "text/html; charset=utf-8"], file_get_contents("./static/push.html"));
        $response->push("/static/app.js");
        $response->push("/static/style.css", ["c" => "c"]);
        return $response;
    }
    if (substr($path, 0, 7) == "/static") {
        $response = new Response(200);
        $response->withFile("." . $path);
        return $response;
    }
};

Http2::runAll();

