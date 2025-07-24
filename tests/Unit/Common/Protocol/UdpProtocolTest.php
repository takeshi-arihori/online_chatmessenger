<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Unit\Common\Protocol;

use ChatMessenger\Common\Protocol\UdpProtocol;
use ChatMessenger\Common\Models\Message;
use ChatMessenger\Common\Exception\ChatMessengerException;
use PHPUnit\Framework\TestCase;

class UdpProtocolTest extends TestCase
{
    private UdpProtocol $protocol;

    protected function setUp(): void
    {
        $this->protocol = new UdpProtocol();
    }

    public function testEncodeBasicMessage(): void
    {
        $data = [
            'username' => 'alice',
            'message' => 'Hello, World!'
        ];

        $encoded = $this->protocol->encode($data);

        // Expected: [5][alice][Hello, World!]
        $expected = pack('C', 5) . 'alice' . 'Hello, World!';
        $this->assertSame($expected, $encoded);
    }

    public function testDecodeBasicMessage(): void
    {
        // Create packet: [5][alice][Hello, World!]
        $packet = pack('C', 5) . 'alice' . 'Hello, World!';

        $decoded = $this->protocol->decode($packet);

        $this->assertSame('alice', $decoded['username']);
        $this->assertSame('Hello, World!', $decoded['message']);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    public function testEncodeDecodeRoundTrip(): void
    {
        $originalData = [
            'username' => 'bob',
            'message' => 'This is a test message.'
        ];

        $encoded = $this->protocol->encode($originalData);
        $decoded = $this->protocol->decode($encoded);

        $this->assertSame($originalData['username'], $decoded['username']);
        $this->assertSame($originalData['message'], $decoded['message']);
    }

    public function testEncodeWithUnicodeCharacters(): void
    {
        $data = [
            'username' => '太郎',
            'message' => 'こんにちは、世界！🌍'
        ];

        $encoded = $this->protocol->encode($data);
        $decoded = $this->protocol->decode($encoded);

        $this->assertSame($data['username'], $decoded['username']);
        $this->assertSame($data['message'], $decoded['message']);
    }

    public function testEncodeEmptyMessage(): void
    {
        $data = [
            'username' => 'alice',
            'message' => ''
        ];

        $encoded = $this->protocol->encode($data);
        $decoded = $this->protocol->decode($encoded);

        $this->assertSame('alice', $decoded['username']);
        $this->assertSame('', $decoded['message']);
    }

    public function testEncodeMaxUsernameLength(): void
    {
        $longUsername = str_repeat('a', 255);
        $data = [
            'username' => $longUsername,
            'message' => 'test'
        ];

        $encoded = $this->protocol->encode($data);
        $decoded = $this->protocol->decode($encoded);

        $this->assertSame($longUsername, $decoded['username']);
        $this->assertSame('test', $decoded['message']);
    }

    public function testEncodeUsernameTooLong(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Username too long');

        $data = [
            'username' => str_repeat('a', 256),
            'message' => 'test'
        ];

        $this->protocol->encode($data);
    }

    public function testEncodeMessageTooLarge(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Message packet too large');

        $data = [
            'username' => 'alice',
            'message' => str_repeat('x', 4096)
        ];

        $this->protocol->encode($data);
    }

    public function testEncodeMissingUsername(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Username and message are required');

        $data = ['message' => 'test'];
        $this->protocol->encode($data);
    }

    public function testEncodeMissingMessage(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Username and message are required');

        $data = ['username' => 'alice'];
        $this->protocol->encode($data);
    }

    public function testDecodeInvalidPacketTooShort(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Invalid packet: too short');

        $this->protocol->decode('');
    }

    public function testDecodeUsernameTruncated(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Invalid packet: username truncated');

        // Packet claims username length is 10 but only provides 5 characters total
        $packet = pack('C', 10) . 'alice';
        $this->protocol->decode($packet);
    }

    public function testDecodeInvalidUtf8(): void
    {
        $this->expectException(ChatMessengerException::class);
        $this->expectExceptionMessage('Invalid UTF-8 encoding');

        // Create packet with invalid UTF-8 bytes
        $packet = pack('C', 5) . "alice" . "\xFF\xFE";
        $this->protocol->decode($packet);
    }

    public function testValidateValidPacket(): void
    {
        $packet = pack('C', 5) . 'alice' . 'Hello!';
        $this->assertTrue($this->protocol->validate($packet));
    }

    public function testValidateInvalidPacket(): void
    {
        $invalidPacket = '';
        $this->assertFalse($this->protocol->validate($invalidPacket));
    }

    public function testGetMaxSize(): void
    {
        $this->assertSame(4096, $this->protocol->getMaxSize());
    }

    public function testEncodeMessage(): void
    {
        $message = new Message('alice', 'Hello, World!');
        $encoded = $this->protocol->encodeMessage($message);

        $expected = pack('C', 5) . 'alice' . 'Hello, World!';
        $this->assertSame($expected, $encoded);
    }

    public function testDecodeToMessage(): void
    {
        $packet = pack('C', 3) . 'bob' . 'Hi there!';
        $message = $this->protocol->decodeToMessage($packet);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('bob', $message->getUsername());
        $this->assertSame('Hi there!', $message->getContent());
    }

    public function testLargeValidMessage(): void
    {
        $username = 'user';
        $largeMessage = str_repeat('x', 4090); // Max size minus username and length byte

        $data = [
            'username' => $username,
            'message' => $largeMessage
        ];

        $encoded = $this->protocol->encode($data);
        $this->assertLessThanOrEqual(4096, strlen($encoded));

        $decoded = $this->protocol->decode($encoded);
        $this->assertSame($username, $decoded['username']);
        $this->assertSame($largeMessage, $decoded['message']);
    }
}
