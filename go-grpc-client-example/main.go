package main

import (
	"context"
	"flag"
	"google.golang.org/grpc/credentials"
	"log"
	"time"

	"grpc/pb"

	"google.golang.org/grpc"
)

// hello_client

const (
	defaultName = "xiao yang guang"
)

var (
	name = flag.String("name", defaultName, "Name to greet")
)

func main() {
	flag.Parse()

	creds, err0 := credentials.NewClientTLSFromFile("./draw.jiangtuan.cn_bundle.pem", "draw.jiangtuan.cn")
	if err0 != nil {
		log.Fatal("证书错误: ", err0)
	}

	// 创建连接
	conn, err := grpc.Dial(":443", grpc.WithTransportCredentials(creds))
	if err != nil {
		log.Fatal("服务端连接失败: ", err)
	}

	// 连接到server端，此处禁用安全传输
	//conn, err := grpc.Dial(*addr, grpc.WithTransportCredentials(insecure.NewCredentials()))
	//if err != nil {
	//	log.Fatalf("did not connect: %v", err)
	//}

	defer conn.Close()
	c := pb.NewGreeterClient(conn)

	// 执行RPC调用并打印收到的响应数据
	ctx, cancel := context.WithTimeout(context.Background(), time.Second)
	defer cancel()
	r, err := c.SayHello(ctx, &pb.HelloRequest{Name: *name})
	if err != nil {
		log.Fatalf("could not greet: %v", err)
	}
	log.Printf("Greeting: %s", r.GetReply())
}
