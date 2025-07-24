<?php

declare(strict_types=1);

namespace ChatMessenger\Common\Models;

class Message
{
    public function __construct(
        private string $username,
        private string $content,
        private ?\DateTimeImmutable $timestamp = null
    ) {
        $this->timestamp = $timestamp ?? new \DateTimeImmutable();
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }
    
    public function getContent(): string
    {
        return $this->content;
    }
    
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
    
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'content' => $this->content,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u')
        ];
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'],
            $data['content'],
            isset($data['timestamp']) ? new \DateTimeImmutable($data['timestamp']) : null
        );
    }
}