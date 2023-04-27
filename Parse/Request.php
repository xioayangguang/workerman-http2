<?php
declare(strict_types=1);

namespace Parse;

use Workerman\Connection\TcpConnection;

class Request
{
    /**
     * Connection.
     * @var Http2Connect
     */
    protected $_client = null;

    /**
     * @var string
     */
    protected $_body = null;

    /**
     * Request $_header.
     * @var array
     */
    protected $_header = null;

    /**
     * @var int
     */
    protected $_streamId = 0;
    /**
     * @var TcpConnection
     */
    private $client;

    /**
     * Request constructor.
     * @param string $buffer
     */
    public function __construct($_streamId, $client, $_header, $_body = "")
    {
        $this->_streamId = $_streamId;
        $this->client = $client;
        $this->_header = $_header;
        $this->_body = $_body;
    }

    /**
     * @param $_body
     */
    public function appendData($_body)
    {
        $this->_body .= $_body;
    }

    /**
     * @param null $name
     * @param null $default
     * @return array
     */
    public function get($name = null, $default = null): array
    {
        return [];
    }

    /**
     * @param null $name
     * @param null $default
     */
    public function post($name = null, $default = null)
    {
    }

    /**
     */
    public function ip()
    {
        return $this->client->getRemotePort();
    }

    /**
     * @return string
     */
    public function host(): string
    {
        return $this->_header["host"];
    }

    /**
     * Get path.
     * @return string
     */
    public function path(): string
    {
        return $this->_header["path"];
    }

    /**
     * Get query string.
     * @return
     */
    public function queryString()
    {
        return $this->_header["query"] ?? "";
    }

    /**
     * @return string
     */
    public function rawBody(): string
    {
        return $this->_body;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->_header["method"];
    }


    public function getStreamId(): int
    {
        return $this->_streamId;
    }


    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->_header;
    }

    /**
     * Parse post.
     * @return array
     */
    protected function parsePost(): array
    {
        return [];
    }
}
