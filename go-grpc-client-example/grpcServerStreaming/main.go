package main

import (
	"context"
	"fmt"
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
	conn, err := grpc.Dial(":446", grpc.WithTransportCredentials(creds))
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

	//服务端流模式
	res, err := c.ServerSayHello(context.Background(), &pb.HelloRequest{Name: "客户端"})
	if err != nil {
		log.Fatalf("could not greet: %v", err)
	}
	for {
		a, err := res.Recv()
		if err != nil {
			fmt.Println(err)
			break
		}
		fmt.Println(a.GetReply())
	}
}
