<?php
declare(strict_types=1);

namespace parse;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class Http2 extends Worker
{
    /**
     * @var string
     */
    const VERSION = '0.0.1';

    /**
     * @var callable
     */
    public $onStreamData;

    /**
     * @var callable
     */
    public $onRequest;

    /**
     * @var callable
     */
    public $onWriteBody;

    /**
     * @var array
     */
    private $clientStreamUrl;

    /**
     * 构造函数
     * @param string $socket_name
     * @param array $context_option
     */
    public function __construct($socket_name, array $context_option = [])
    {
        if (strpos($socket_name, "ssl") === 0) {
            if (!isset($context_option['ssl']["local_cert"], $context_option['ssl']["local_pk"])) {
                throw new \Exception("Currently only ssl-based handshake is implemented");
            }
            $context_option['ssl']['alpn_protocols'] = 'h2';
        }
        parent::__construct($socket_name, $context_option);
        $backtrace = debug_backtrace();
        $this->_autoloadRootPath = dirname($backtrace[0]['file']);
    }


    public function run(): void
    {
        if (!is_callable($this->onRequest)) {
            throw new \Exception("Must have a business processing function");
        }
        $this->onConnect = array($this, 'onClientConnect');
        parent::run();
    }


    public function onClientConnect(TcpConnection $connection): void
    {
        $client = new Http2Connect($connection);
        $parser = new Http2Parser(
            $client,
            $this->onStreamData,
            $this->onRequest,
            $this->onWriteBody,
            $this->clientStreamUrl ?? []
        );
        $connection->onMessage = function (TcpConnection $connection, $data) use ($parser) {
            $parser->parse($data, $connection);
        };
        $connection->onClose = function (TcpConnection $connection) use ($parser) {
            $parser->onClose();
        };
    }

    public function setClientStreamUrl(array $url): void
    {
        $this->clientStreamUrl = $url;
    }
}