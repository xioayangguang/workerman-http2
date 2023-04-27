package main

import (
	"context"
	"fmt"
	"google.golang.org/grpc"
	"google.golang.org/grpc/credentials"
	"grpc/pb"
	"log"
	"sync"
	"time"
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
	conn, err := grpc.Dial(":448", grpc.WithTransportCredentials(creds))
	//conn, err := grpc.Dial("localhost:50052", grpc.WithInsecure())
	if err != nil {
		log.Fatal("服务端连接失败: ", err)
	}

	defer conn.Close()
	c := pb.NewGreeterClient(conn)

	allStr, _ := c.DoubleSayHello(context.Background())
	wg := sync.WaitGroup{}
	wg.Add(2)
	// 不停的接收数据
	go func() {
		defer wg.Done()
		for {
			data, err := allStr.Recv()
			if err != nil {
				fmt.Println(err)
			}
			fmt.Println("收到客户端消息：" + data.Reply)
		}
	}()
	// 不停的发送数据
	go func() {
		defer wg.Done()
		var i = 0
		for {
			i++
			_ = allStr.Send(&pb.HelloRequest{Name: fmt.Sprintf("客户端数据流%v", i)})
			time.Sleep(time.Second * 1)
		}
	}()
	wg.Wait()
}
