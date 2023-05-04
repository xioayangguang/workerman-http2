<?php
declare(strict_types=1);

namespace frame;

use exception\InvalidFrameException;
use parse\Frame;
use parse\Http2Parser;

class SettingsFrame extends Frame
{
    protected $defined_flags = [Flag::ACK];
    protected $type = 0x04;
    protected $stream_association = self::NO_STREAM;
    protected $settings;

    const HEADER_TABLE_SIZE = 0x01;

    const ENABLE_PUSH = 0x02;

    const MAX_CONCURRENT_STREAMS = 0x03;

    const INITIAL_WINDOW_SIZE = 0x04;

    const MAX_FRAME_SIZE = 0x05;

    const MAX_HEADER_LIST_SIZE = 0x06;

    /**
     * SettingsFrame constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $options['settings'] = $options['settings'] ?? [];
        if ($options['settings'] && $this->flags->hasFlag(Flag::ACK)) {
            throw new InvalidFrameException("Settings must be empty if ACK flag is set", Http2Parser::PROTOCOL_ERROR);
        }
        $this->settings = $options['settings'];
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        $settings = [];
        foreach ($this->settings as $setting => $value) {
            $settings[] = pack('nN', $setting & 0xFF, $value);
        }
        return implode('', $settings);
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        if (strlen($data) > 0) {

            $frameLength = $this->getLength();
            if ($frameLength % 6 !== 0) {
                throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
            }
            if ($frameLength > 60) {
                throw new InvalidFrameException("Excessive SETTINGS frame", Http2Parser::PROTOCOL_ERROR);
            }
            foreach (range(0, strlen($data) - 1, 6) as $i) {
                if (!$unpack = @unpack('nname/Nvalue', substr($data, $i, $i + 6))) {
                    throw new InvalidFrameException("Invalid SETTINGS body", Http2Parser::PROTOCOL_ERROR);
                }
                $name = $unpack['name'];
                $value = $unpack['value'];
                $this->settings[$name] = $value;
            }
        }
        $this->body_len = strlen($data);
    }

    /**
     * @return array|mixed
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param array|mixed $settings
     * @return SettingsFrame
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
        return $this;
    }
}
