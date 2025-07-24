<?php

declare(strict_types=1);

namespace ChatMessenger\Common\Protocol;

use ChatMessenger\Common\Models\Message;
use ChatMessenger\Common\Exception\ChatMessengerException;

class UdpProtocol implements ProtocolInterface
{
    private const MAX_MESSAGE_SIZE = 4096;
    private const MAX_USERNAME_LENGTH = 255;

    /**
     * @param array<string, mixed> $data
     */
    public function encode(array $data): string
    {
        if (!isset($data['username'], $data['message'])) {
            throw new ChatMessengerException('Username and message are required');
        }

        $username = $data['username'];
        $message = $data['message'];

        // Validate username length (in bytes, not characters)
        $usernameBytes = strlen($username);
        if ($usernameBytes > self::MAX_USERNAME_LENGTH) {
            throw new ChatMessengerException('Username too long (max ' . self::MAX_USERNAME_LENGTH . ' bytes)');
        }

        // Encode the packet: [usernamelen: 1byte][username: variable][message: variable]
        $packet = pack('C', $usernameBytes) . $username . $message;

        // Validate total packet size
        if (strlen($packet) > self::MAX_MESSAGE_SIZE) {
            throw new ChatMessengerException('Message packet too large (max ' . self::MAX_MESSAGE_SIZE . ' bytes)');
        }

        return $packet;
    }

    /**
     * @return array{username: string, message: string, timestamp: string}
     */
    public function decode(string $data): array
    {
        if (strlen($data) < 1) {
            throw new ChatMessengerException('Invalid packet: too short');
        }

        // Extract username length (first byte)
        $unpacked = unpack('C', substr($data, 0, 1));
        if ($unpacked === false) {
            throw new ChatMessengerException('Invalid packet: cannot unpack username length');
        }
        $usernameLength = $unpacked[1];

        // Validate packet structure
        if (strlen($data) < 1 + $usernameLength) {
            throw new ChatMessengerException('Invalid packet: username truncated');
        }

        // Extract username
        $username = substr($data, 1, $usernameLength);

        // Extract message (remaining bytes)
        $message = substr($data, 1 + $usernameLength);

        // Validate UTF-8 encoding
        if (!mb_check_encoding($username, 'UTF-8') || !mb_check_encoding($message, 'UTF-8')) {
            throw new ChatMessengerException('Invalid UTF-8 encoding');
        }

        return [
            'username' => $username,
            'message' => $message,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.u')
        ];
    }

    public function validate(string $data): bool
    {
        try {
            $this->decode($data);
            return true;
        } catch (ChatMessengerException) {
            return false;
        }
    }

    public function getMaxSize(): int
    {
        return self::MAX_MESSAGE_SIZE;
    }

    public function encodeMessage(Message $message): string
    {
        return $this->encode([
            'username' => $message->getUsername(),
            'message' => $message->getContent()
        ]);
    }

    public function decodeToMessage(string $data): Message
    {
        $decoded = $this->decode($data);
        return Message::fromArray($decoded);
    }
}
