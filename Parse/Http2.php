<?php
declare(strict_types=1);

namespace Parse;

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
    private $business;

    /**
     * 构造函数
     * @param string $socket_name
     * @param array $context_option
     */
    public function __construct($socket_name, array $context_option, callable $business)
    {
        if (!isset($context_option['ssl']["local_cert"], $context_option['ssl']["local_pk"])) {
            return new \Exception("Currently only ssl-based handshake is implemented");
        }
        if (!is_callable($business)) {
            return new \Exception("Must have a business processing function");
        }
        $context_option['ssl']['alpn_protocols'] = 'h2, http/1.1';
        parent::__construct($socket_name, $context_option);
        $backtrace = debug_backtrace();
        $this->_autoloadRootPath = dirname($backtrace[0]['file']);
        $this->business = $business;
    }


    public function run(): void
    {
        $this->onConnect = array($this, 'onClientConnect');
        parent::run();
    }


    public function onClientConnect(TcpConnection $connection): void
    {
        //todo 定时检查状态，关闭不用的链接
        $client = new Http2Connect($connection);
        $parser = new Http2Parser($client, $this->business);
        $connection->onMessage = function (TcpConnection $connection, $data) use ($parser) {
            $parser->parse($data);
        };
    }
}