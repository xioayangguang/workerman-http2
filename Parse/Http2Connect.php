<?php
declare(strict_types=1);

namespace Parse;

use Workerman\Connection\TcpConnection;

class Http2Connect
{
    /**
     * @var TcpConnection
     */
    public $connection;

    private $expirationTime;

    public function __construct(TcpConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getRemoteAddress(): string
    {
        return $this->connection->getRemoteAddress();
    }

    public function getLocalAddress(): string
    {
        return $this->connection->getLocalAddress();
    }

    public function getPort(): int
    {
        return $this->connection->getLocalPort();
    }

    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }

    public function updateExpirationTime(int $param)
    {
        $this->expirationTime = $param;
    }

    public function close()
    {
        $this->connection->close();
    }
}