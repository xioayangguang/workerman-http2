<?php
declare(strict_types=1);

namespace parse;

use Workerman\Connection\TcpConnection;

/**
 * 不准备支持cookie  session
 */
class Request
{
    /**
     * @var string
     */
    private $rawBody = null;

    /**
     * Request $_header.
     * @var array
     */
    private $_header = null;

    /**
     * @var int
     */
    private $_streamId = 0;
    /**
     * @var TcpConnection
     */
    private $client;

    /**
     * 里面是body解析出来的数据
     * @var
     */
    private $_data;


    /**
     * Request constructor.
     * @param string $buffer
     */
    public function __construct($_streamId, $client, $_header, $_body = "")
    {
        $this->_streamId = $_streamId;
        $this->client = $client;
        foreach ($_header as $key => $value) {
            if (is_array($value)) {
                $_header[$key] = $value[0] ?? "";
            }
        }
        $this->_header = $_header;
        $this->rawBody = $_body;
    }

    /**
     * @param $_body
     */
    public function appendData($_body)
    {
        $this->rawBody .= $_body;
    }

    /**
     * @param null $name
     * @param null $default
     * @return array
     */
    public function get($name = null, $default = null): array
    {
        \parse_str($this->_header["query"] ?? "", $get);
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
        if (!isset($this->_data['post'])) {
            $this->parsePost();
        }
        if (null === $name) {
            return $this->_data['post'] ?? [];
        }
        return $this->_data['post'][$name] ?? $default;
    }

    /**
     * @param null $name
     * @return array|mixed|null
     */
    public function file($name = null)
    {
        if (!isset($this->_data['files'])) {
            $this->parsePost();
        }
        if (null === $name) {
            return $this->_data['files'] ?? [];
        }
        return $this->_data['files'][$name] ?? null;
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
        return $this->rawBody;
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

    /**
     * 抄的workerman的
     */
    protected function parsePost()
    {
        if (!$this->rawBody) return;
        $this->_data['post'] = $this->_data['files'] = [];
        $content_type = $this->header("content-type");
        if ($content_type and \preg_match('/boundary="?(\S+)"?/', $content_type, $match)) {
            $http_post_boundary = '--' . $match[1];
            $this->parseUploadFiles($http_post_boundary);
            return;
        }
        if (\preg_match('/\bjson\b/i', $content_type)) {
            $this->_data['post'] = (array)json_decode($this->rawBody, true);
        } else {
            \parse_str($this->rawBody, $this->_data['post']);
        }
    }

    /**
     * Parse upload files.
     * @param string $http_post_boundary
     * @return void
     */
    protected function parseUploadFiles($http_post_boundary)
    {
        $http_post_boundary = \trim($http_post_boundary, '"');
        $http_body = $this->rawBody();
        $http_body = \substr($http_body, 0, \strlen($http_body) - (\strlen($http_post_boundary) + 4));
        $boundary_data_array = \explode($http_post_boundary . "\r\n", $http_body);
        if ($boundary_data_array[0] === '' || $boundary_data_array[0] === "\r\n") {
            unset($boundary_data_array[0]);
        }
        $key = -1;
        $files = array();
        foreach ($boundary_data_array as $boundary_data_buffer) {
            list($boundary_header_buffer, $boundary_value) = \explode("\r\n\r\n", $boundary_data_buffer, 2);
            // Remove \r\n from the end of buffer.
            $boundary_value = \substr($boundary_value, 0, -2);
            $key++;
            foreach (\explode("\r\n", $boundary_header_buffer) as $item) {
                list($header_key, $header_value) = \explode(": ", $item);
                $header_key = \strtolower($header_key);
                switch ($header_key) {
                    case "content-disposition":
                        // Is file data.
                        if (\preg_match('/name="(.*?)"; filename="(.*?)"/i', $header_value, $match)) {
                            $error = 0;
                            $tmp_file = '';
                            $size = \strlen($boundary_value);
                            $tmp_upload_dir = "./temp/";
                            if (!$tmp_upload_dir) {
                                $error = UPLOAD_ERR_NO_TMP_DIR;
                            } else {
                                $tmp_file = \tempnam($tmp_upload_dir, 'workerman.upload.');
                                if ($tmp_file === false || false == \file_put_contents($tmp_file, $boundary_value)) {
                                    $error = UPLOAD_ERR_CANT_WRITE;
                                }
                            }
                            if (!isset($files[$key])) {
                                $files[$key] = array();
                            }
                            // Parse upload files.
                            $files[$key] += array(
                                'key' => $match[1],
                                'name' => $match[2],
                                'tmp_name' => $tmp_file,
                                'size' => $size,
                                'error' => $error
                            );
                            break;
                        } // Is post field.
                        else {
                            // Parse $_POST.
                            if (\preg_match('/name="(.*?)"$/', $header_value, $match)) {
                                $this->_data['post'][$match[1]] = $boundary_value;
                            }
                        }
                        break;
                    case "content-type":
                        // add file_type
                        if (!isset($files[$key])) {
                            $files[$key] = array();
                        }
                        $files[$key]['type'] = \trim($header_value);
                        break;
                }
            }
        }
        foreach ($files as $file) {
            $key = $file['key'];
            unset($file['key']);
            // Multi files
            if (\strlen($key) > 2 && \substr($key, -2) == '[]') {
                $key = \substr($key, 0, -2);
                $this->_data['files'][$key][] = $file;
            } else {
                $this->_data['files'][$key] = $file;
            }
        }
    }
}
