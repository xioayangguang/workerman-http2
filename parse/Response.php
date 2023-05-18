<?php
declare(strict_types=1);

namespace parse;

class Response
{
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
     * Response constructor.
     * @param int $status
     * @param array $headers
     * @param string $body
     */
    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        $this->_status = $status;
        $this->_header = $headers;
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
        $this->_header[$name] = $value;
        return $this;
    }

    /**
     * Get header.
     * @param string $name
     * @return null|array|string
     */
    public function getHeader($name)
    {
        if (!isset($this->_header[$name])) {
            return null;
        }
        return $this->_header[$name];
    }

    /**
     * Get headers.
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->_header;
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
        return [];
        //return [
        //    [
        //        "uri" => "",
        //        "header" => [],
        //    ], [
        //        "uri" => "",
        //        "header" => [],
        //    ]
        //];
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
     * 在响应前写入数据，只是在客户端非流式传输的时候有效
     * @param string $data
     */
    public function tuckData(string $data)
    {
        if ($this->http2Driver instanceof Http2Driver) {
            $this->http2Driver->writeData($data, $this->streamId);
        }
    }
}