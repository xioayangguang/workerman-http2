<?php
declare(strict_types=1);

namespace parse;

class Response
{
    /**
     * @var null
     */
    protected static $_mimeTypeMap = null;

    /**
     * Header data.
     * @var array
     */
    protected $_header = null;

    /**
     * Http status.
     * @var int
     */
    protected $_status = null;

    /**
     * Http body.
     * @var string
     */
    protected $_body = null;

    /**
     * @var array
     */
    protected $_trailers = [];

    /**
     * @var Http2Driver
     */
    public $http2Driver;
    /**
     * @var int
     */
    public $streamId;

    /**
     * @var array
     */
    public $push = [];

    /**
     * Response constructor.
     * @param int $status
     * @param array $headers
     * @param string $body
     */
    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        $this->_status = $status;
        foreach ($headers as $k => $v) {
            $this->_header[strtolower($k)] = [$v];
        }
        $this->_body = $body;
    }

    /**
     * Set header.
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function header(string $name, string $value)
    {
        $this->_header[strtolower($name)] = [$value];
        return $this;
    }

    /**
     * Get headers.
     */
    public function getHeaders($name = null)
    {
        if ($name == null) {
            return $this->_header ?? [];
        }
        return $this->_header[$name] ?? "";
    }

    public function getStatus(): int
    {
        return $this->_status;
    }

    public function getBody(): string
    {
        return $this->_body;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
    }


    /**
     * 推送 预加载
     * @return array
     */
    public function getPushes(): array
    {
        return $this->push;
    }

    public function push(string $url, array $header = [])
    {
        $urlInfo = parse_url($url);
        if (is_array($urlInfo)) {
            $this->push[] = [
                "uri" => $urlInfo,
                "header" => $header
            ];
        }
    }

    /**
     * @return array
     */
    public function getTrailers(): array
    {
        return $this->_trailers;
        //return [
        //    "test" => "value",
        //    "test1" => "value",
        //    "test2" => "value"
        //];
    }

    public function setTrailers(array $trailers)
    {
        $this->_trailers = $trailers;
    }


    /**
     * @param $file
     * @param int $offset
     * @param int $length
     */
    public function withFile($file, int $offset = 0, int $length = 0)
    {
        if (!\is_file($file)) {
            $this->_status = 404;
            $this->header("content-type", "text/html");
            $this->_body = "<h3>404 Not Found</h3>";
            return;
        }
        $file_info = \pathinfo($file);
        $extension = $file_info['extension'] ?? '';
        $base_name = $file_info['basename'] ?? 'unknown';
        if (!isset($headers['content-type'])) {
            if (isset(self::$_mimeTypeMap[$extension])) {
                $this->header("content-type", self::$_mimeTypeMap[$extension]);
            } else {
                $this->header("content-type", "application/octet-stream");
            }
        }
        if (!isset($headers['content-disposition']) && !isset(self::$_mimeTypeMap[$extension])) {
            $this->header("content-disposition", 'attachment; filename="' . $base_name . '"');
        }
        if (!isset($headers['last-modified'])) {
            if ($mtime = \filemtime($file)) {
                $this->header("last-modified", \gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
            }
        }
        $file_size = (int)\filesize($file);
        $body_len = $length > 0 ? $length : $file_size - $offset;
        $this->header('accept-ranges', 'bytes');
        if ($offset || $length) {
            $offset_end = $offset + $body_len - 1;
            $this->header('content-range', "bytes $offset-$offset_end/$file_size");
        }
        if ($body_len < 2 * 1024 * 1024) {
            $this->_body = file_get_contents($file, false, null, $offset, $body_len);
        }
    }

    /**
     * 在响应前写入数据，只是在客户端非流式传输的时候有效
     * @param string $data
     */
    public function tuckData(string $data)
    {
        if ($this->http2Driver instanceof Http2Driver) {
            $this->http2Driver->writeData($data, $this->streamId);
        }
    }

    public static function init()
    {
        $mime_file = __DIR__ . '/mime.types';
        $items = \file($mime_file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        foreach ($items as $content) {
            if (\preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
                $mime_type = $match[1];
                $extension_var = $match[2];
                $extension_array = \explode(' ', \substr($extension_var, 0, -1));
                foreach ($extension_array as $file_extension) {
                    static::$_mimeTypeMap[$file_extension] = $mime_type;
                }
            }
        }
    }
}

Response::init();