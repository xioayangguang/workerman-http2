<?php
declare(strict_types=1);

namespace Parse;

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
     * Request constructor.
     * @param string $buffer
     */
    public function __construct($client, $_header, $_body = "")
    {
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
     * @return string
     */
    public function host(): string
    {
    }

    /**
     * Get uri.
     * @return string
     */
    public function uri(): string
    {
    }

    /**
     * Get path.
     * @return string
     */
    public function path(): string
    {
    }

    /**
     * Get query string.
     * @return
     */
    public function queryString()
    {
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
        return "GET";
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
