<?php
declare(strict_types=1);

namespace parse;

use Workerman\Connection\TcpConnection;

class Request
{
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
        \parse_str($this->_header["query"], $get);
        if (null === $name) {
            return $get;
        }
        return $get[$name] ?? $default;
    }

    /**
     * @param null $name
     * @param null $default
     * @return array|mixed|string|null
     */
    public function post($name = null, $default = null)
    {
        $post = [];
        if ($this->_body === '') {
            return "";
        }
        $content_type = $this->_header['content-type'] ?? "";
        if (\preg_match('/\bjson\b/i', $content_type)) {
            return (array)json_decode($this->_body, true);
        } else {
            \parse_str($this->_body, $post);
        }
        if (null === $name) {
            return $post;
        }
        return $post[$name] ?? $default;
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
        return $this->_header["path"] ?? "/";
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


    /**
     * @return int
     */
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
     * Get header item by name.
     * @param string|null $name
     * @param mixed|null $default
     * @return array|string|null
     */
    public function header(string $name = null, $default = null)
    {
        if (null === $name) {
            return $this->_header;
        }
        $name = \strtolower($name);
        return $this->_header[$name] ?? $default;
    }
}
