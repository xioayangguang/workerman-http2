<?php
declare(strict_types=1);

namespace Parse;

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
     * Phrases.
     * @var array
     */
    protected static $_phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

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
}