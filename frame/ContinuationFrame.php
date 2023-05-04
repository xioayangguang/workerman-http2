<?php
declare(strict_types=1);

namespace frame;

use parse\Frame;

class ContinuationFrame extends Frame
{
    protected $defined_flags = [Flag::END_HEADERS];
    protected $type = 0x09;
    protected $stream_association = self::HAS_STREAM;
    protected $data;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->data = $options['data'] ?? '';
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        $this->data = $data;
        $this->body_len = strlen($data);
    }

    /**
     * @param mixed|string $data
     * @return ContinuationFrame
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getData()
    {
        return $this->data;
    }
}
