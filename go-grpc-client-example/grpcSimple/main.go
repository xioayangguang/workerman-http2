package main

import (
	"context"
	"google.golang.org/grpc"
	"google.golang.org/grpc/credentials"
	"grpc/pb"
	"log"
)

const (
	defaultName = "xiao yang guang"
)

func main() {
	creds, err0 := credentials.NewClientTLSFromFile("../draw.jiangtuan.cn_bundle.pem", "draw.jiangtuan.cn")
	if err0 != nil {
		log.Fatal("证书错误: ", err0)
	}
	// 创建连接
	conn, err := grpc.Dial(":443", grpc.WithTransportCredentials(creds))
	if err != nil {
		log.Fatal("服务端连接失败: ", err)
	}
	defer conn.Close()
	c := pb.NewGreeterClient(conn)
	//// 简单模式
	r, err := c.SayHello(context.Background(), &pb.HelloRequest{Name: "xiao yang guang"})
	if err != nil {
		log.Fatalf("could not greet: %v", err)
	}
	log.Printf("Greeting: %s", r.GetReply())
}
