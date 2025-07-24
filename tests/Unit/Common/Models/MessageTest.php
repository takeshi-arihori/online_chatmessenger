<?php

declare(strict_types=1);

namespace ChatMessenger\Tests\Unit\Common\Models;

use ChatMessenger\Common\Models\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testCreateMessage(): void
    {
        $username = 'testuser';
        $content = 'Hello, World!';
        
        $message = new Message($username, $content);
        
        $this->assertSame($username, $message->getUsername());
        $this->assertSame($content, $message->getContent());
        $this->assertInstanceOf(\DateTimeImmutable::class, $message->getTimestamp());
    }
    
    public function testCreateMessageWithTimestamp(): void
    {
        $username = 'testuser';
        $content = 'Hello, World!';
        $timestamp = new \DateTimeImmutable('2023-01-01 12:00:00');
        
        $message = new Message($username, $content, $timestamp);
        
        $this->assertSame($timestamp, $message->getTimestamp());
    }
    
    public function testToArray(): void
    {
        $username = 'testuser';
        $content = 'Hello, World!';
        $timestamp = new \DateTimeImmutable('2023-01-01 12:00:00.123456');
        
        $message = new Message($username, $content, $timestamp);
        $array = $message->toArray();
        
        $this->assertSame($username, $array['username']);
        $this->assertSame($content, $array['content']);
        $this->assertSame('2023-01-01 12:00:00.123456', $array['timestamp']);
    }
    
    public function testFromArray(): void
    {
        $data = [
            'username' => 'testuser',
            'content' => 'Hello, World!',
            'timestamp' => '2023-01-01 12:00:00.123456'
        ];
        
        $message = Message::fromArray($data);
        
        $this->assertSame($data['username'], $message->getUsername());
        $this->assertSame($data['content'], $message->getContent());
        $this->assertEquals(new \DateTimeImmutable($data['timestamp']), $message->getTimestamp());
    }
}