# 基于workerman 实现http2服务端

### 运行http2服务端的方式

* 添加host
127.0.0.1 xxxxxxxx.cn


* 修改证书路径

```
'ssl' => [
     'local_cert' => './draw.xxx.cn_bundle.pem', //修改成自己的路径
     'local_pk' => './draw.xxx.cn.key', // 修改成自己的路径
]
```

运行： 

```
composer install

php start.php start
or 
start.bat  
```

* 浏览器打开
https://xxxx.cn/
![img.png](./example/pic/img.png)

  
* grpc 运行结果(注意自己的证书路径)
![img_2.png](./example/pic/img_2.png)
