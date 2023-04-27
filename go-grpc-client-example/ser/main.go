package main

import (
	"fmt"
	"google.golang.org/grpc"
	"grpc/pb"
	"net"
	"sync"
	"time"
)

// 服务
type GreeterServer struct {
	pb.UnimplementedGreeterServer
}

// 实现方法
func (s *GreeterServer) DoubleSayHello(allStr pb.Greeter_DoubleSayHelloServer) error {
	wg := sync.WaitGroup{}
	wg.Add(2)
	// 不停的接收数据
	go func() {
		defer wg.Done()
		for {
			data, _ := allStr.Recv()
			fmt.Println("收到客户端消息：" + data.Name)
		}
	}()

	// 不停的发送数据
	go func() {
		defer wg.Done()
		for {
			_ = allStr.Send(&pb.HelloResponse{Reply: "我是服务器"})
			time.Sleep(time.Second)
		}
	}()

	wg.Wait()
	return nil
}

func main() {
	// 创建监听
	lis, err := net.Listen("tcp", "0.0.0.0:50052")
	if err != nil {
		panic(err)
	}

	// 创建服务
	s := grpc.NewServer()

	// 注册服务
	pb.RegisterGreeterServer(s, &GreeterServer{})

	// 启动服务
	err = s.Serve(lis)
	if err != nil {
		panic(err)
	}
}
