# 基于workerman 实现http2

* 协议实现目前不完善 目前握手方式只实现ssl协商上层协议

* 以后有时间在此基础上实现 php的grpc服务端

* 修改证书

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

php start.php start
```

* 浏览器打开
https://xxxxxx.cn
