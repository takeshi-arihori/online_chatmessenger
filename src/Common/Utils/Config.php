<?php

declare(strict_types=1);

namespace ChatMessenger\Common\Utils;

class Config
{
    private const DEFAULT_UDP_PORT = 8080;
    private const DEFAULT_TCP_PORT = 8081;
    private const MAX_MESSAGE_SIZE = 4096;
    private const MAX_USERNAME_LENGTH = 255;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaults(), $config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function getUdpPort(): int
    {
        return $this->get('udp_port', self::DEFAULT_UDP_PORT);
    }

    public function getTcpPort(): int
    {
        return $this->get('tcp_port', self::DEFAULT_TCP_PORT);
    }

    public function getMaxMessageSize(): int
    {
        return $this->get('max_message_size', self::MAX_MESSAGE_SIZE);
    }

    public function getMaxUsernameLength(): int
    {
        return $this->get('max_username_length', self::MAX_USERNAME_LENGTH);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaults(): array
    {
        return [
            'udp_port' => self::DEFAULT_UDP_PORT,
            'tcp_port' => self::DEFAULT_TCP_PORT,
            'max_message_size' => self::MAX_MESSAGE_SIZE,
            'max_username_length' => self::MAX_USERNAME_LENGTH,
            'encoding' => 'UTF-8',
            'timeout' => 30,
        ];
    }
}
