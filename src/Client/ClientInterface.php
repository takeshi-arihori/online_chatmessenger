<?php

declare(strict_types=1);

namespace ChatMessenger\Client;

interface ClientInterface
{
    /**
     * Connect to the server
     */
    public function connect(string $host, int $port): bool;

    /**
     * Disconnect from the server
     */
    public function disconnect(): void;

    /**
     * Check if client is connected
     */
    public function isConnected(): bool;

    /**
     * Send a message to the server
     */
    public function sendMessage(string $message): bool;

    /**
     * Start the client
     */
    public function start(): void;

    /**
     * Stop the client
     */
    public function stop(): void;
}
