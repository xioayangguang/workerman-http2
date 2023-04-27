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
	creds, err0 := credentials.NewClientTLSFromFile("./draw.jiangtuan.cn_bundle.pem", "draw.jiangtuan.cn")
	if err0 != nil {
		log.Fatal("证书错误: ", err0)
	}
	// 创建连接
	conn, err := grpc.Dial(":444", grpc.WithTransportCredentials(creds))
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

	//// 简单模式
	r, err := c.SayHello(context.Background(), &pb.HelloRequest{Name: "xiao yang guang"})
	if err != nil {
		log.Fatalf("could not greet: %v", err)
	}
	log.Printf("Greeting: %s", r.GetReply())

	//服务端流模式
	//res, err := c.ServerSayHello(context.Background(), &pb.HelloRequest{Name: "客户端"})
	//if err != nil {
	//	log.Fatalf("could not greet: %v", err)
	//}
	//for {
	//	a, err := res.Recv()
	//	if err != nil {
	//		fmt.Println(err)
	//		break
	//	}
	//	fmt.Println(a.GetReply())
	//}

	//客户端流模式
	//putS, _ := c.ClientSayHello(context.Background())
	//i := 0
	//for {
	//	i++
	//	_ = putS.Send(&pb.HelloRequest{
	//		Name: fmt.Sprintf("客户端流数据 %d", i),
	//	})
	//	time.Sleep(time.Second)
	//	if i > 10000 {
	//		break
	//	}
	//}

	// 双向流模式
	//allStr, _ := c.DoubleSayHello(context.Background())
	//wg := sync.WaitGroup{}
	//wg.Add(2)
	//// 不停的接收数据
	//go func() {
	//	defer wg.Done()
	//	for {
	//		data, err := allStr.Recv()
	//		if err != nil {
	//			fmt.Println(err)
	//		}
	//		fmt.Println("收到客户端消息：" + data.Reply)
	//	}
	//}()
	//// 不停的发送数据
	//go func() {
	//	defer wg.Done()
	//	var i = 0
	//	for {
	//		i++
	//		_ = allStr.Send(&pb.HelloRequest{Name: fmt.Sprintf("客户端数据流%v", i)})
	//		time.Sleep(time.Second * 1)
	//	}
	//}()
	//wg.Wait()
}
