<?php
declare(strict_types=1);

namespace parse;

use exception\Http2ConnectionException;
use exception\Http2StreamException;
use frame\ContinuationFrame;
use frame\DataFrame;
use frame\Flag;
use frame\GoAwayFrame;
use frame\HeadersFrame;
use frame\PingFrame;
use frame\PriorityFrame;
use frame\PushPromiseFrame;
use frame\RstStreamFrame;
use frame\SettingsFrame;
use frame\WindowUpdateFrame;
use hpack\HPack;
use Workerman\Connection\TcpConnection;

final class Http2Parser
{
    public const PREFACE = "PRI * HTTP/2.0\r\n\r\nSM\r\n\r\n";

    private const DEFAULT_MAX_FRAME_SIZE = 1 << 14;

    private const HEADER_NAME_REGEX = '/^[\x21-\x40\x5b-\x7e]+$/';

    public const KNOWN_REQUEST_PSEUDO_HEADERS = [
        ":method" => true,
        ":authority" => true,
        ":path" => true,
        ":scheme" => true,
    ];
    // SETTINGS Flags
    public const ACK = 0x01;
    // HEADERS Flags
    public const NO_FLAG = 0x00;
    public const END_STREAM = 0x01;
    public const END_HEADERS = 0x04;
    public const PADDED = 0x08;
    public const PRIORITY_FLAG = 0x20;
    // Frame Types
    public const DATA = 0x00;
    public const HEADERS = 0x01;
    public const PRIORITY = 0x02;
    public const RST_STREAM = 0x03;
    public const SETTINGS = 0x04;
    public const PUSH_PROMISE = 0x05;
    public const PING = 0x06;
    public const GOAWAY = 0x07;
    public const WINDOW_UPDATE = 0x08;
    public const CONTINUATION = 0x09;
    // Settings
    public const HEADER_TABLE_SIZE = 0x1; // 1 << 12
    public const ENABLE_PUSH = 0x2; // 1
    public const MAX_CONCURRENT_STREAMS = 0x3; // INF
    public const INITIAL_WINDOW_SIZE = 0x4; // 1 << 16 - 1   //当前设置为初始化窗口大小
    public const MAX_FRAME_SIZE = 0x5; // 1 << 14
    public const MAX_HEADER_LIST_SIZE = 0x6; // INF
    // Error codes
    public const GRACEFUL_SHUTDOWN = 0x0;
    public const PROTOCOL_ERROR = 0x1;
    public const INTERNAL_ERROR = 0x2;
    public const FLOW_CONTROL_ERROR = 0x3;
    public const SETTINGS_TIMEOUT = 0x4;
    public const STREAM_CLOSED = 0x5;
    public const FRAME_SIZE_ERROR = 0x6;
    public const REFUSED_STREAM = 0x7;
    public const CANCEL = 0x8;
    public const COMPRESSION_ERROR = 0x9;
    public const CONNECT_ERROR = 0xa;
    public const ENHANCE_YOUR_CALM = 0xb;
    public const INADEQUATE_SECURITY = 0xc;
    public const HTTP_1_1_REQUIRED = 0xd;
    //NO_ERROR (0x0，没有错误)：关联的条件不是错误的结果。例如，GOAWAY帧可以包含此错误码，表明优雅地关闭连接。
    //PROTOCOL_ERROR (0x1，协议错误)：端点检测到的没有特别指定的协议错误。适用于无法提供更加具体的错误码的情况。
    //INTERNAL_ERROR (0x2，内部错误)：端点遇到的意外的内部错误。
    //FLOW_CONTROL_ERROR (0x3，流量控制错误)：端点检测到对端违反了流量控制协议。
    //SETTINGS_TIMEOUT (0x4，设置超时)：端点发送了一个SETTINGS帧，但是没有及时收到响应。
    //STREAM_CLOSED (0x5，流关闭)：端点在流半关闭之后收到一个帧。
    //FRAME_SIZE_ERROR (0x6，帧大小错误)：端点收到的帧的大小无效。
    //REFUSED_STREAM (0x7，被拒绝的流)：端点在执行任何应用处理之前拒绝一个流。
    //CANCEL (0x8，取消)：被端点用于表明不再需要指定的流。
    //COMPRESSION_ERROR (0x9，压缩错误)：端点不能为连接维护报头压缩的上下文。
    //CONNECT_ERROR (0xa，连接错误)：为某个连接请求建立的连接被重置或被不正常地关闭。
    //ENHANCE_YOUR_CALM (0xb，提高你的稳定性)：端点检测到远端表现出有可能产生过大负载的行为。
    //INADEQUATE_SECURITY (0xc，安全性不够)：底层传输存在不满足最低安全需求的属性。
    //HTTP_1_1_REQUIRED (0xd，需要HTTP1.1)：端点要求使用HTTP1.1来代替HTTP/2。

    /** @var Http2Driver */
    private $handler;

    /** @var int */
    private $headerSizeLimit = self::DEFAULT_MAX_FRAME_SIZE;

    /** @var bool 期待下一个帧是头信息的延续帧 */
    private $continuationExpected = false;

    /** @var int */
    private $headerFrameType = 0;

    /** @var string */
    private $headerBuffer = '';

    /** @var int */
    private $headerStream = 0;

    /** @var HPack */
    private $hpack;

    /**
     * http2是否握手成功
     * @var bool
     */
    private $handsFlag = false;

    /**
     * @var Http2Connect
     */
    private $http2Connect;

    /**
     * 获取到的数据
     * @var string
     */
    private $dataBuffer = "";
    /**
     * 是否http1.1升级成功
     * @var bool
     */
    private $upgrade;
    /**
     * @var \Workerman\Protocols\Http\Request
     */
    private $upgradeRequest;

    /**
     * @param Http2Connect $http2Connect
     * @param Options $options
     * @param callable $business
     */
    public function __construct(Http2Connect $http2Connect, $onStreamData, $onRequest, $onWriteBody, $clientStreamUrl)
    {
        $this->http2Connect = $http2Connect;
        $this->hpack = new HPack();
        $this->handler = new Http2Driver($http2Connect, $onStreamData, $onRequest, $onWriteBody, $clientStreamUrl, $this->hpack);
    }

    /**
     * @param $data
     * @throws Http2ConnectionException
     */
    public function parse($data, TcpConnection $connection)
    {
        $length = strlen($data);
        self::DebugLog("========================data in {$length}===========================");
        $this->dataBuffer = $this->dataBuffer . $data;
        if (!$this->handsFlag) {
            if (strpos($this->dataBuffer, "HTTP/1.")) {  //初略判断http1 //h2c升级握手升级部分
                if ($connection->transport == "ssl") {
                    $this->http2Connect->connection->send("HTTP/1.1 400 Bad Request\r\nContent-Type: text/html;\r\ncharset=utf-8\r\nContent-Length: 19\r\n\r\nnot support http1.x");
                    return;
                }
                $header_end_pos = \strpos($this->dataBuffer, "\r\n\r\n");
                if (!$header_end_pos) {
                    $this->http2Connect->connection->send("HTTP/1.1 400 Bad Request\r\nContent-Type: text/html;\r\ncharset=utf-8\r\nContent-Length: 19\r\n\r\nnot support http1.x");
                    return;
                }
                if (!\preg_match("/HTTP2-Settings: *(.*?)\r\n/i", $this->dataBuffer, $match)) {
                    $this->http2Connect->connection->send("HTTP/1.1 400 Bad Request\r\nContent-Type: text/html;\r\ncharset=utf-8\r\nContent-Length: 19\r\n\r\nnot support http1.x");
                    return;
                }
                $f = new SettingsFrame();
                $f->parseBody(\base64_decode(\strtr($match[1], "-_", "+/"), true));
                $this->parseSettings($f);
                $this->upgradeRequest = new \Workerman\Protocols\Http\Request($this->dataBuffer);
                $handshake_message = "HTTP/1.1 101 Switching Protocols\r\n"
                    . "Connection: Upgrade\r\n"
                    . "Upgrade: h2c\r\n\r\n";
                self::DebugLog("发送101同意升级");
                $this->http2Connect->connection->send($handshake_message);
                $this->dataBuffer = "";
                $this->upgrade = true;
                return;
            } else {
                if (0 === \strpos($this->dataBuffer, self::PREFACE)) {
                    $this->dataBuffer = \substr($this->dataBuffer, \strlen(self::PREFACE));
                } else {
                    $this->http2Connect->connection->close();
                }
                $this->handsFlag = true;
                $this->handler->writeFrame(\pack("nNnNnNnN",
                    self::INITIAL_WINDOW_SIZE,
                    Options::getBodySizeLimit(),
                    Http2Parser::MAX_CONCURRENT_STREAMS,
                    Options::getConcurrentStreamLimit(),
                    Http2Parser::MAX_HEADER_LIST_SIZE,
                    Options::getHeaderSizeLimit(),
                    Http2Parser::MAX_FRAME_SIZE,
                    self::DEFAULT_MAX_FRAME_SIZE
                ), Http2Parser::SETTINGS, Http2Parser::NO_FLAG);
            }
            if ($this->upgrade) {
                $this->handler->upgrade($this->http2Connect, $this->upgradeRequest);
            }
        }
        try {
            while (true) {
                if (strlen($this->dataBuffer) < 9) break;
                $f = Frame::parseFrameHeader(substr($this->dataBuffer, 0, 9));
                [
                    'length' => $frameLength,
                    'type' => $frameType,
                    'flags' => $frameFlags,
                    'id' => $streamId,
                ] = \unpack('Nlength/ctype/cflags/Nid', "\0" . substr($this->dataBuffer, 0, 9));
                $length = $f->getLength();
                if (strlen($this->dataBuffer) < 9 + $length) break;
                $f->parseBody(substr($this->dataBuffer, 9, $length));
                $this->dataBuffer = substr($this->dataBuffer, 9 + $length);
                if ($f->getLength() > self::DEFAULT_MAX_FRAME_SIZE) {
                    throw new Http2ConnectionException("Frame size limit exceeded", self::FRAME_SIZE_ERROR);
                }
                //期待下一个帧是头信息的延续帧
                if ($this->continuationExpected && !$f instanceof ContinuationFrame) {
                    throw new Http2ConnectionException("Expected continuation frame", self::PROTOCOL_ERROR);
                }
                $streamId &= 0x7fffffff;
                self::LogFrame('接收到数据', $frameType, $frameFlags, $streamId, $frameLength);
                if ($f instanceof SettingsFrame) {
                    $this->parseSettings($f); //已调试
                } else if ($f instanceof WindowUpdateFrame) {
                    $this->parseWindowUpdate($f); //已调试
                } else if ($f instanceof PriorityFrame) {
                    $this->parsePriorityFrame($f);  //已调试
                } else if ($f instanceof RstStreamFrame) {
                    $this->parseStreamReset($f);//已调试
                } else if ($f instanceof PingFrame) {
                    $this->parsePing($f); //已调试
                } else if ($f instanceof GoAwayFrame) {
                    $this->parseGoAway($f); //已调试
                } else if ($f instanceof PushPromiseFrame) {
                    $this->parsePushPromise($f); //已调试//客户端不应该发送此类型帧
                } else if ($f instanceof HeadersFrame) {
                    $this->parseHeaders($f);  //已调试
                } else if ($f instanceof ContinuationFrame) {
                    //只要相同流上的前导帧是没有设置END_HEADERS标记的HEADERS，PUSH_PROMISE，或CONTINUATION帧，
                    //就可以发送任意数量的CONTINUATION帧
                    $this->parseContinuation($f);  //已调试
                } else if ($f instanceof DataFrame) {
                    $this->parseDataFrame($f);
                }
            }
        } catch (Http2StreamException $exception) {
            self::Log((string)$exception);
            $this->handler->handleStreamException($exception);
        } catch (Http2ConnectionException $exception) {
            self::Log((string)$exception);
            $this->handler->handleConnectionException($exception);
        } catch (\Exception $exception) {
            self::Log((string)$exception);
            $this->handler->handleConnectionException(new Http2ConnectionException("PROTOCOL_ERROR", self::PROTOCOL_ERROR));
        }
    }

    /**
     * @throws Http2ConnectionException
     * @throws Http2StreamException
     */
    private function parseDataFrame(DataFrame $f): void
    {
        $data = $f->getData();
        $streamId = $f->getStreamId();
        $this->handler->handleData($streamId, $data);
        if ($f->getFlags()->hasFlag(Flag::END_STREAM)) {
            $this->handler->handleStreamEnd($streamId);
        }
    }

    /**
     * @throws Http2ConnectionException
     * @throws Http2StreamException
     */
    private function parsePushPromise(PushPromiseFrame $f): void
    {
        $pushId = $f->getPromisedStreamId();
        $streamId = $f->getStreamId();
        $this->headerFrameType = self::PUSH_PROMISE;
        //向头里面添加内容
        $this->pushHeaderBlockFragment($pushId, $f->getData());
        if ($f->getFlags()->hasFlag(Flag::END_HEADERS)) {
            $this->continuationExpected = false;
            [$pseudo, $headers] = $this->parseHeaderBuffer();
            $this->handler->handlePushPromise($streamId, $pushId, $pseudo, $headers);
        } else {
            $this->continuationExpected = true;
        }
        if ($f->getFlags()->hasFlag(Flag::END_STREAM)) {
            $this->handler->handleStreamEnd($streamId);
        }
    }


    /**
     * @throws Http2ConnectionException
     * @throws Http2StreamException
     */
    private function parseHeaders(HeadersFrame $f): void
    {
        $streamId = $f->getStreamId();
        $isPriority = $f->getFlags()->hasFlag(Flag::PRIORITY); //优先级权重
        if ($isPriority) {
            $parent = $f->getDependsOn();
            $weight = $f->getStreamWeight();
            $parent &= 0x7fffffff;
            $this->handler->handlePriority($streamId, $parent, $weight + 1);
        }
        $this->headerFrameType = self::HEADERS;
        $this->pushHeaderBlockFragment($streamId, $f->getData());
        $ended = $f->getFlags()->hasFlag(Flag::END_STREAM);
        if ($f->getFlags()->hasFlag(Flag::END_HEADERS)) {
            $this->continuationExpected = false;
            $headersTooLarge = \strlen($this->headerBuffer) > $this->headerSizeLimit;
            [$pseudo, $headers] = $this->parseHeaderBuffer();
            if ($headersTooLarge) {
                throw new Http2StreamException(
                    "Headers exceed maximum configured size of {$this->headerSizeLimit} bytes",
                    $streamId,
                    self::ENHANCE_YOUR_CALM
                );
            }
            $this->handler->handleHeaders($streamId, $pseudo, $headers, $ended);
        } else {
            $this->continuationExpected = true;
        }
        if ($ended) {
            $this->handler->handleStreamEnd($streamId);
        }
    }

    /**
     * @throws Http2ConnectionException
     */
    private function parsePriorityFrame(PriorityFrame $f): void
    {
        $weight = $f->getStreamWeight();
        $parent = $f->getDependsOn();
        if ($parent & 0x80000000) {
            $parent &= 0x7fffffff;
        }
        $streamId = $f->getStreamId();
        $this->handler->handlePriority($streamId, $parent, $weight + 1);
    }

    /**
     * @throws Http2ConnectionException
     */
    private function parseStreamReset(RstStreamFrame $f): void
    {
        $streamId = $f->getStreamId();
        $errorCode = $f->getErrorCode();
        $this->handler->handleStreamReset($streamId, $errorCode);
    }

    /**
     * @throws Http2ConnectionException
     */
    private function parseSettings(SettingsFrame $f): void
    {
        if ($f->getFlags()->hasFlag(Flag::ACK)) {
            return;
        }
        $settings = $f->getSettings();
        $this->handler->handleSettings($settings);
    }

    /**
     * @throws Http2ConnectionException
     */
    private function parsePing(PingFrame $f): void
    {
        $frameBuffer = $f->getOpaqueData();
        if ($f->getFlags()->hasFlag(Flag::ACK)) {
            $this->handler->handlePong($frameBuffer);
        } else {
            $this->handler->handlePing($frameBuffer);
        }
    }

    /**
     * @throws Http2ConnectionException
     */
    private function parseGoAway(GoAwayFrame $f): void
    {
        $lastId = $f->getLastStreamId();
        $error = $f->getErrorCode();
        $this->handler->handleShutdown($lastId & 0x7fffffff, $error);
    }

    /**
     * @throws Http2ConnectionException
     * @throws Http2StreamException
     */
    private function parseWindowUpdate(WindowUpdateFrame $f): void
    {
        $streamId = $f->getStreamId();
        $windowSize = $f->getWindowIncrement();
        if ($streamId) {
            $this->handler->handleStreamWindowIncrement($streamId, $windowSize);
        } else {
            $this->handler->handleConnectionWindowIncrement($windowSize);
        }
    }

    private function parseContinuation(ContinuationFrame $f): void
    {
        $streamId = $f->getStreamId();
        if ($streamId !== $this->headerStream) {
            throw new Http2ConnectionException("Invalid CONTINUATION frame stream ID", self::PROTOCOL_ERROR);
        }
        if ($this->headerBuffer === '') {
            throw new Http2ConnectionException("Unexpected CONTINUATION frame for stream ID " . $this->headerStream, self::PROTOCOL_ERROR);
        }
        $frameBuffer = $f->getData();
        $this->pushHeaderBlockFragment($streamId, $frameBuffer);
        $ended = $f->getFlags()->hasFlag(Flag::END_STREAM);
        if ($f->getFlags()->hasFlag(Flag::END_HEADERS)) {
            $this->continuationExpected = false;
            $isPush = $this->headerFrameType === self::PUSH_PROMISE;
            $pushId = $this->headerStream;
            [$pseudo, $headers] = $this->parseHeaderBuffer();
            if ($isPush) { //如果是push的持续帧
                $this->handler->handlePushPromise($streamId, $pushId, $pseudo, $headers);
            } else {
                $this->handler->handleHeaders($streamId, $pseudo, $headers, $ended);
            }
        }
        if ($ended) {
            $this->handler->handleStreamEnd($streamId);
        }
    }

    /**
     * 从缓冲区解析http请求头
     * @throws Http2StreamException
     * @throws Http2ConnectionException
     */
    private function parseHeaderBuffer(): array
    {
        if ($this->headerStream === 0) {
            throw new Http2ConnectionException('Invalid stream ID 0 for header block', self::PROTOCOL_ERROR);
        }
        if ($this->headerBuffer === '') {
            throw new Http2StreamException('Invalid empty header section', $this->headerStream, self::PROTOCOL_ERROR);
        }
        $decoded = $this->hpack->decode($this->headerBuffer, $this->headerSizeLimit);
        if ($decoded === null) {
            throw new Http2ConnectionException("Compression error in headers", self::COMPRESSION_ERROR);
        }
        $headers = [];
        $pseudo = [];
        foreach ($decoded as [$name, $value]) {
            if (!\preg_match(self::HEADER_NAME_REGEX, $name)) {
                throw new Http2StreamException("Invalid header field name", $this->headerStream, self::PROTOCOL_ERROR);
            }
            if ($name[0] === ':') {
                if (!empty($headers)) {
                    throw new Http2ConnectionException("Pseudo header after other headers", self::PROTOCOL_ERROR);
                }
                if (isset($pseudo[$name])) {
                    throw new Http2ConnectionException("Repeat pseudo header", self::PROTOCOL_ERROR);
                }
                $pseudo[$name] = $value;
                continue;
            }
            $headers[$name][] = $value;
        }
        $this->headerBuffer = '';
        $this->headerStream = 0;
        return [$pseudo, $headers];
    }

    /**
     * 向请求数据的header数据 缓冲区添加数据
     * @throws Http2ConnectionException
     */
    private function pushHeaderBlockFragment(int $streamId, string $buffer): void
    {
        if ($this->headerStream !== 0 && $this->headerStream !== $streamId) {
            throw new Http2ConnectionException("Expected CONTINUATION frame for stream ID " . $this->headerStream, self::PROTOCOL_ERROR);
        }
        $this->headerStream = $streamId;
        $this->headerBuffer .= $buffer;
    }

    public static function LogFrame(string $action, int $frameType, int $frameFlags, int $streamId, int $frameLength, string $action1 = "")
    {
        $flags = "";
        if ($frameFlags & self::END_STREAM) {
            $flags .= "END_STREAM ";
        } else if ($frameFlags & self::END_HEADERS) {
            $flags .= "END_HEADERS ";
        } else if ($frameFlags & self::PADDED) {
            $flags .= "PADDED ";
        } else if ($frameFlags & self::PRIORITY_FLAG) {
            $flags .= "PRIORITY_FLAG ";
        } else {
            $flags .= "NO_FLAG ";
        }
        self::DebugLog($action . ' ' . Frame::FRAMES[$frameType] . ' flags = ' . $flags . ', stream = ' . $streamId . ', length = ' . $frameLength . "   " . $action1);
    }

    public static function DebugLog(string $msg)
    {
        if (Options::isInDebugMode()) {
            fwrite(\STDOUT, $msg . "\r\n");
        }
    }

    public static function Log(string $msg)
    {
        file_put_contents(Options::logFile(), $msg . "\r\n", FILE_APPEND);
    }

    public function onClose()
    {
//        $this->handler->stop();
    }
}
