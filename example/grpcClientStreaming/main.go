package main

import (
	"context"
	"fmt"
	"google.golang.org/grpc"
	"google.golang.org/grpc/credentials"
	"grpc/pb"
	"log"
	"time"
)

const (
	defaultName = "xiao yang guang"
)

func main() {
	creds, err0 := credentials.NewClientTLSFromFile("./key/draw.jiangtuan.cn_bundle.pem", "draw.jiangtuan.cn")
	if err0 != nil {
		log.Fatal("证书错误: ", err0)
	}
	// 创建连接
	conn, err := grpc.Dial(":447", grpc.WithTransportCredentials(creds))
	//conn, err := grpc.Dial("localhost:50052", grpc.WithInsecure())
	if err != nil {
		log.Fatal("服务端连接失败: ", err)
	}

	// 连接到server端，此处禁用安全传输
	//conn, err := grpc.Dial(":444", grpc.WithTransportCredentials(insecure.NewCredentials()))
	//if err != nil {
	//	log.Fatalf("did not connect: %v", err)
	//}

	defer conn.Close()
	c := pb.NewGreeterClient(conn)

	//客户端流模式
	putS, _ := c.ClientSayHello(context.Background())
	i := 0
	for {
		i++
		_ = putS.Send(&pb.HelloRequest{
			Name: fmt.Sprintf("客户端流数据 %d", i),
		})
		time.Sleep(time.Second)
		if i > 10000 {
			break
		}
	}
}
