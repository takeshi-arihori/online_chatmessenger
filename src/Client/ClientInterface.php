<?php

declare(strict_types=1);

namespace ChatMessenger\Client;

interface ClientInterface
{
    public function connect(string $host, int $port): bool;

    public function disconnect(): void;

    public function sendMessage(string $message): bool;

    public function isConnected(): bool;
}
