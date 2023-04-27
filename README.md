# 基于workerman 实现http2服务端

### 运行http2服务端的方式
* 修改证书  http2_server.php

```
'ssl' => [
     'local_cert' => './draw.jiangtuan.cn_bundle.pem', //修改成自己的路径
     'local_pk' => './draw.jiangtuan.cn.key', // 修改成自己的路径
     'verify_peer' => false,
     'allow_self_signed' => true,
]
```

运行方式： 

```
composer install
```


```
php http2_server.php start
```

* 浏览器打开
https://xxxxxx
![img.png](./pic/img.png)



# 基于Http2实现Grpc服务端
### 运行grpc服务端的方式

* 修改证书  grpc_server.php

```
'ssl' => [
     'local_cert' => './draw.jiangtuan.cn_bundle.pem', //修改成自己的路径
     'local_pk' => './draw.jiangtuan.cn.key', // 修改成自己的路径
     'verify_peer' => false,
     'allow_self_signed' => true,
]
```

运行方式：

```
composer install
```

### windows运行如下
```
php  http2_server.php grpc_server_simple.php grpc_server_server_streaming.php grpc_server_client_streaming.php grpc_server_bidirectional_streaming.php

```

### Linux运行如下

```
php  http2_server.php start  
php  .... start  
以此类推
```

* 进入go-grpc-client-example 修改main.go证书为自己的

```
creds, err0 := credentials.NewClientTLSFromFile("./draw.jiangtuan.cn_bundle.pem", "draw.jiangtuan.cn")

```

* 运行grpc客户端
```
cd grpcSimple
go run main.go

....
以此类推
```
* 运行结果
![img_2.png](./pic/img_2.png)
