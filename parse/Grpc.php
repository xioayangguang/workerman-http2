<?php

namespace parse;

use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

//todo  grpc错误代码处理
class Grpc extends Http2
{

    /**
     * @var string
     */
    private static $body = "";
    /**
     * @var array[]
     */
    private static $streaming = [
        "client_streaming" => [],
        "double_streaming" => [],
        "server_streaming" => [],
        "simple" => [],
    ];
    /**
     * @var array
     */
    private static $route = [];
    /**
     * @var array
     */
    private static $parameter = [];

    /**
     * @var string
     */
    private static $protocPath = "";


    /**
     * @throws \Exception
     */
    public function __construct($socket_name, $protocPath, array $context_option = [])
    {
        parent::__construct($socket_name, $context_option);
        $backtrace = debug_backtrace();
        $this->_autoloadRootPath = dirname($backtrace[0]['file']);
        $this->onStreamData = [$this, "onStreamData"];
        $this->onRequest = [$this, "onRequest"];
        $this->onWriteBody = [$this, "onWriteBody"];
        self::$protocPath = $protocPath;
    }

    public function run(): void
    {
        parent::run();
        self::loadHook();
        $this->clientStreamUrl = array_merge(self::$streaming["client_streaming"] ?? [], self::$streaming["double_streaming"] ?? []);
    }


    public static function pack(string $data): string
    {
        return pack('CN', 0, strlen($data)) . $data;
    }


    public static function unpack(string $data): string
    {
        self::$body .= $data;
        if (strlen(self::$body) < 5) return "";
        $data = unpack('Cpack/Nleng', substr(self::$body, 0, 5));
        if (strlen(self::$body) < 5 + $data["leng"]) return "";
        $grpcData = substr(self::$body, 5, $data["leng"]);
        self::$body = substr(self::$body, 5 + $data["leng"]);
        return $grpcData;
    }


    public static function onStreamData(Request $request, Response $response, string $data)
    {
        $data = self::unpack($data);
        if (!empty($data)) {
            if (is_callable(self::$route[$request->path()])) {
                $obj = new self::$parameter[$request->path()];
                $obj->mergeFromString($data);
                $obj->metadata = $request->header();
                $responseMessage = (self::$route[$request->path()])($obj);
                if (in_array($request->path(), self::$streaming["client_streaming"])) {
                    if (!empty($responseMessage)) {
                        //todo 如果客户端主动关闭客户端收不到任何数据客户端会报错
                        $data = $responseMessage->serializeToString();
                        $response->setBody(self::pack($data));
                        return false;
                    }
                }
                if (in_array($request->path(), self::$streaming["double_streaming"])) {
                    $generator = $responseMessage;
                    if ($generator instanceof Iterator) {
                        foreach ($generator as $responseMessage) {
                            $data = $responseMessage->serializeToString();
                            $response->tuckData(self::pack($data));   //在响应流前追加数据
                        }
                    } else {//终止流
                        $response->setTrailers(["grpc-status" => 14]);
                        return false;
                    }
                    if (isset($responseMessage->endStreaming) and $responseMessage->endStreaming === true) {//终止流
                        $response->setBody();
                        return false;
                    }
                }
            } else {//这里不传入end  如果是结束直接传Response对象
                $response->setTrailers(["grpc-status" => 5]);
                return false;
            }
        }
    }


    //只应该再简单模式下生效
    public static function onRequest(Request $request)
    {
        if (in_array($request->path(), self::$streaming["simple"])) { //普通模式
            $data = self::unpack($request->rawBody());
            if (!empty($data)) {
                if (is_callable(self::$route[$request->path()])) {
                    $obj = new  self::$parameter[$request->path()];
                    $obj->metadata = $request->header();
                    $obj->mergeFromString($data);
                    $responseMessage = (self::$route[$request->path()])($obj);
                    $data = $responseMessage->serializeToString();
                    $response = new Response(200, ['content-type' => 'application/grpc'], self::pack($data));
                    $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
                    return $response;
                } else {
                    $response = new Response(200, ['content-type' => 'application/grpc'], self::pack(""));
                    $response->setTrailers(["grpc-status" => "5", "grpc-message" => ""]);
                    return $response;
                }
            }
        } else {
            $response = new Response(200, ['content-type' => 'application/grpc'], self::pack(""));
            $response->setTrailers(["grpc-status" => "0", "grpc-message" => ""]);
            return $response;
        }
    }


    public static function onWriteBody(Request $request, Response $response)
    {
        if (in_array($request->path(), self::$streaming["server_streaming"])) {
            try {
                if (is_callable(self::$route[$request->path()])) {
                    $obj = new self::$parameter[$request->path()];
                    $obj->metadata = $request->header();
                    $obj->mergeFromString(self::unpack($request->rawBody()));
                    $generator = (self::$route[$request->path()])($obj);
                    if ($generator instanceof Iterator) {
                        foreach ($generator as $responseMessage) {
                            $data = $responseMessage->serializeToString();
                            $response->tuckData(self::pack($data));   //在响应流前追加数据
                        }
                    }
                }
            } catch (\Exception $exception) {
                $response->setTrailers(["grpc-status" => "14", "grpc-message" => $exception->getMessage()]);
            } finally {
                $response->setBody();
            }
        }
    }

    public static function serializeToString($response): string
    {
        if (method_exists($response, 'encode')) {
            $data = $response->encode();
        } elseif (method_exists($response, 'serializeToString')) {
            $data = $response->serializeToString();
        } else {
            $data = $response->serialize();
        }
        return self::pack($data);
    }

    /**
     * 扫描文件
     * @throws \Exception
     */
    private static function loadHook()
    {
        $base_path = realpath(__DIR__ . '/../');
        $path = $base_path . '/' . self::$protocPath . '/';
        if (!file_exists($path)) {
            throw new \Exception("protoc path set error");
        }
        $dir_iterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            $path_info = pathinfo($file);
            if ($path_info['extension'] != 'php') continue;
            $file = "{$path_info['dirname']}/{$path_info['filename']}";
            $file = str_replace("/", "\\", $file);
            $file = substr($file, strlen($base_path));
            if (property_exists($file, 'Streaming') and property_exists($file, 'Route') and property_exists($file, 'Parameter')) {
                //todo 验证二维数组合并会不会出问题
                self::$streaming = array_merge(self::$streaming, $file::$Streaming);
                self::$route = array_merge(self::$route, $file::$Route);
                self::$parameter = array_merge(self::$parameter, $file::$Parameter);
            }
        }
    }
}


//grpc的状态码在google.golang.org/grpc/codes：codes中
//0：Ok：返回成功
//1：Canceled：操作已取消
//2：Unknown：未知错误。如果从另一个地址空间接收到的状态值属 于在该地址空间中未知的错误空间，则可以返回此错误的示例。 没有返回足够的错误信息的API引发的错误也可能会转换为此错误
//3：InvalidArgument：表示客户端指定了无效的参数。 请注意，这与FailedPrecondition不同。 它表示无论系统状态如何（例如格式错误的文件名）都有问题的参数
//4：DeadlineExceeded：意味着操作在完成之前过期。 对于更改系统状态的操作，即使操作成功完成，也可能会返回此错误。 例如，服务器的成功响应可能会延迟足够的时间以使截止日期到期
//5：NotFound：表示找不到某个请求的实体（例如文件或目录）
//6：AlreadyExists：表示尝试创建实体失败，因为已经存在
//7：PermissionDenied：表示调用者没有执行指定操作的权限。它不能用于因耗尽某些资源而引起的拒绝（使用ResourceExhausted代替这些错误）。如果调用者无法识别，则不能使用它（使用Unauthenticated代替这些错误）
//8：ResourceExhausted：表示某些资源已耗尽，可能是每个用户的配额，或者整个文件系统空间不足
//9：FailedPrecondition：表示操作被拒绝，因为系统不处于操作执行所需的状态。例如，要删除的目录可能不是空的，rmdir操作应用于非目录等。可能帮助服务实现者判断FailedPrecondition，Aborted和Unavailable之间的试金石测试：使用不可用如果客户端只能重试失败的呼叫。如果客户端应该在更高级别重试（例如，重新启动读取 - 修改 - 写入序列），则使用中止。如果客户端不应该重试直到系统状态被明确修复，则使用FailedPrecondition。例如，如果“rmdir”由于目录非空而失败，应该返回FailedPrecondition，因为客户端不应该重试，除非他们首先通过从目录中删除文件来修复该目录。如果客户端在资源上执行条件REST获取/更新/删除并且服务器上的资源与条件不匹配，则使用FailedPrecondition。例如，在相同的资源上发生冲突的读取 - 修改 - 写入
//10：Aborted：表示操作被中止，通常是由于并发问题（如序列器检查失败，事务异常终止等）造成的。请参阅上面的试金石测试以确定FailedPrecondition，Aborted和Unavailable之间的差异
//11：OutOfRange：表示操作尝试超过有效范围。 例如，寻找或阅读文件末尾。 与InvalidArgument不同，此错误表示如果系统状态更改可能会解决的问题。 例如，如果要求读取的偏移量不在[0,2 ^ 32-1]范围内，则32位文件系统将生成InvalidArgument，但如果要求从偏移量读取当前值，则它将生成OutOfRange 文件大小。 FailedPrecondition和OutOfRange之间有相当多的重叠。 我们建议在应用时使用OutOfRange（更具体的错误），以便遍历空间的调用者可以轻松查找OutOfRange错误以检测何时完成
//12：Unimplemented：表示此服务中未执行或未支持/启用操作
//13：Internal： 意味着底层系统预期的一些不变量已被打破。 如果你看到其中的一个错误，那么事情就会非常糟糕
//14：Unavailable：表示服务当前不可用。这很可能是一种暂时性情况，可能会通过退避重试来纠正。请参阅上面的试金石测试以确定FailedPrecondition，Aborted和Unavailable之间的差异
//15：DataLoss：指示不可恢复的数据丢失或损坏
//16：Unauthenticated：表示请求没有有效的操作认证凭证
//17：_maxCode：这个是最大的状态码